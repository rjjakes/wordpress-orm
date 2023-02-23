# WORM (Wordpress ORM)

*Under active development*

A lightweight, Doctrine style ORM for Wordpress 4.8+. Requires PHP 5.5+

This library borrows a lot of concepts from Doctrine for Symfony including the mapper, entity manager, repositories,
one-to-many entity relationships, lazy-loading of related entities and a query builder.  

It acts as a layer sitting on top of the internal Wordpress `$wpdb` class. 

All queries are run through the internal Wordpress `$wpdb->prepare()` function to protect against SQL injection
attacks. 

## Why?

I came to Wordpress from a Symfony development background. I wanted to work with objects instead of manually writing
SQL queries. I looked around for a good ORM and was unable to find anything that fulfilled my requirements. 

Getting Doctrine to work with Wordpress was complex and seemed like overkill as it's a very heavy weight library. 

There's also: https://github.com/tareq1988/wp-eloquent which is really good, but is more of a query builder for
existing Wordpress tables.

## Version history

master branch = active development

0.1.0 tag = previous master. The version most people would be using circa last 2022.  

## Installation

```
composer require rjjakes/wordpress-orm
```

## Usage

### Create a model

To use the ORM, first create a class that extends the ORM base model and add a number of properties as protected
variables. The property names must exactly match the desired SQL column names (there is no name mapping).
 
Note that a property of `$id` is added to the model when you do `extends BaseModel` so you do not need to add that.   

Example:

```php
<?php

namespace App\Models;

use Symlink\ORM\Models\BaseModel as Model;

/**
 * @ORM_Type Entity
 * @ORM_Table "product"
 * @ORM_AllowSchemaUpdate True
 */
class Product extends Model {

  /**
   * @ORM_Column_Type TEXT
   */
  protected $title;

  /**
   * @ORM_Column_Type datetime
   */
  protected $time;

  /**
   * @ORM_Column_Type smallint
   * @ORM_Column_Length 5
   * @ORM_Column_Null NOT NULL
   */
  protected $views;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 32
   * @ORM_Column_Null NOT NULL
   */
  protected $short_name;

}

```

**The class annotations are as follows:**

`ORM_Type` Should always be "Entity"

`ORM_Table` This is the actual table name in the database without the wordpress suffix. So with a default Wordpress
installation, this will create a table called: `wp_product` in your database.  

`ORM_AllowSchemaUpdate` This allows or blocks a database schema update with the updateSchema() function (see below).
For your custom models, you want to set this to True. When mapping models to existing Wordpress tables (such as
wp_posts), this should be set to False to avoid corrupting that table.      

**The property annotations are as follows:**

`ORM_Column_Type` The MySQL column type.

`ORM_Column_Length` The MySQL column length.

`ORM_Column_Null` The MySQL column NULL setting. (The only valid option here is 'NOT NULL'). For NULL, just omit this
annotation. 


### Update the database schema

Once you have a model object, you need to tell Wordpress to create a table to match your model. 

Use the mapper class:

```php
use Symlink\ORM\Mapping;
```


First, get an instance of the ORM mapper object. This static function below makes sure `new Mapping()` is called once
per request. Any subsequent calls to `::getMapper()` will return the same object. This means you don't need to
continually create a new instance of Mapper() and parse the annotations.     

```php
$mapper = Mapping::getMapper();
```

Now update the database schema for this class as follows: 

```php
$mapper->updateSchema(Product::class);
```

This function uses the internal Wordpress dbDelta system to compare and update database tables. If your table doesn't
exist, it will be created, otherwise it checks the schema matches the model and modifies the database table if needed.
 
You should only run this function either when your plugin is activated or during development when you know you have
made a change to your model schema and want to apply it to the database 

### Persisting objects to the database. 

Use the ORM manager: 

```php
use Symlink\ORM\Manager;
```

Create a new instance of your model:

```php
$product = new Product();
$product->set('title', 'Some title');
$product->set('time', '2017-11-03 10:04:02');
$product->set('views', 34);
$product->set('short_name', 'something_here');
```

Get an instance of the ORM manager class. Like the Mapping class, this static function returns
a reusable instance of the manager class. 

```php
$orm = Manager::getManager();
```

Now queue up these changes to apply to the database. Calling this does NOT immediately apply the changes to the
database. The idea here is the same as Doctrine: you can queue up many different changes to happen and once you're
ready to apply them, the ORM will combine these changes into single SQL queries where possible. This helps reduce the 
number of calls made to the database. 

```php
$orm->persist($product);
```

Once, you're ready to apply all changes to your database (syncing what you have persisted to the database), call
flush():

```php
$orm->flush();
```

Now check your database and you'll see a new row containing your model data. 

### Querying the database

Use the ORM manager: 

```php
use Symlink\ORM\Manager;
```

Get an instance of the ORM manager class. 

```php
$orm = Manager::getManager();
```

Get the object repository. Repositories are classes that are specific to certain object types. They contain functions
for querying specific object types. 

By default all object types have a base repository which you can get access to by passing in the object type as follows:

```php
$repository = $orm->getRepository(Product::class);
```

**With the query builder**

You can create a query though this repository like so:

```php
$query = $repository->createQueryBuilder()
  ->where('ID', 3, '=')
  ->orderBy('ID', 'ASC')
  ->limit(1)
  ->buildQuery();
```

Available where() operators are: 

```php
'<', '<=', '=', '!=', '>', '>=', 'IN', 'NOT IN'
```

Available orderBy() operators are: 

```php
'ASC', 'DESC'
```

To use the "IN" and "NOT IN" clauses of the ->where() function, pass in an array of values like so:

```php
$query = $repository->createQueryBuilder()
  ->where('id', [1, 12], 'NOT IN')
  ->orderBy('id', 'ASC')
  ->limit(1)
  ->buildQuery();
```

Now you have your query, you can use it to get some objects back out of the database.

```php
$results = $query->getResults();
```

Note that if there was just one result, `$results` will contain an object of the repository type. Otherwise it will
contain an array of objects. 
 
To force `getResults()` to always return an array (even if it's just one results), call it with `TRUE` like this:  

```php
$results = $query->getResults(TRUE);
```

**Built-in repository query functions**

Building a query every time you want to select objects from the database is not best practice. Ideally you would create
some helper functions that abstract the query builder away from your controller. 
 
There are several built-in functions in the base repository. 

Return an object by id:

```php
$results = Manager::getManager()
            ->getRepository(Product::class)
            ->find($id);
```
 
Return all objects sorted by ascending id. 

```php
$results = Manager::getManager()
            ->getRepository(Product::class)
            ->findAll();
```
 
Return all objects matching pairs of property name and value: 

```php
$results = Manager::getManager()
            ->getRepository(Product::class)
            ->findBy([$property_name_1 => $value_1, $property_name_2 => $value_2]);
```
 
To add more repository query functions, you can subclass the `BaseRepository` class and tell your object to use that
instead of `BaseRepository`. That is covered in the section below called: *Create a custom repository*   


### Saving modified objects back to the database

To modify an object, load the object from the database modfiy one or more of it's values, call `flush()` to apply the
changes back to the database.
 
For example:
 
 ```php
$orm = Manager::getManager();
$repository = $orm->getRepository(Product::class);

$product = $repository->find(9);   // Load an object by known ID=9
$product->set('title', 'TITLE HAS CHANGED!');

$orm->flush();
```

This works because whenever an object is persisted ot loaded from the database, Wordpres ORM tracks any changes made to
the model data. `flush()` syncronizes the differences made since the load (or last `flush()`). 

### Deleting objects from the database

To remove an object from the database, load an object from the database and pass it to the `remove()` method on the
manager class. Then call `flush()` to syncronize the database. 

For example:

```php
$orm = Manager::getManager();
$repository = $orm->getRepository(Product::class);

$product = $repository->find(9);   // Load an object by known ID=9

$orm->remove($product);   // Queue up the object to be removed from the database. 

$orm->flush();
```

### Dropping model tables from the database.

It's good practice for your plugin to clean up any data it has created when the user uninstalls. With that in mind, the
ORM has a method for removing previously created tables. If you have created any custom models, you should use this 
function as part of your uninstall hook.  

Use the mapper class:

```php
use Symlink\ORM\Mapping;
```


First, get an instance of the ORM mapper object.    

```php
$mapper = Mapping::getMapper();
```

Now pass the model classname to dropTable() like this:

```php
$mapper->dropTable(Product::class);
```


### Create a custom repository

@todo

### Relationships

@todo

## Exceptions

Wordpress ORM uses Exceptions to handle most failure states. You'll want to wrap calls to ORM functions in 
`try {} catch() {}` blocks to handle these exceptions and send errors or warning messages to the user.
    
For example:
    
```php
try {
    $query = $repository->createQueryBuilder()
      ->where('ID', 3, '==')  // Equals operator should be '=' not '=='
      ->buildQuery();
} catch (\Symlink\ORM\Exceptions\InvalidOperatorException $e) {
    // ... warn the user about the bad operator or handle it another way. 
}

```
    
The exceptions are as follows.     

```php
AllowSchemaUpdateIsFalseException
FailedToInsertException
InvalidOperatorException
NoQueryException
PropertyDoesNotExistException
RepositoryClassNotDefinedException
RequiredAnnotationMissingException
UnknownColumnTypeException
```


## Pre-defined models

@todo


## Dependencies 

(Dependencies are automatically handled by Composer). 

https://github.com/marcioalmada/annotations

https://github.com/myclabs/deepcopy


## Credits

Maintained by: https://github.com/rjjakes

This library is under active development, so I'll happily accept comments, issues and pull requests. 

