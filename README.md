## @todo

- Functions to quote and escape text fields. 

```
$metakey = "Harriet's Adages";
$metavalue = "WordPress' database interface is like Sunday Morning: Easy.";

$wpdb->query( $wpdb->prepare( 
	"
		INSERT INTO $wpdb->postmeta
		( post_id, meta_key, meta_value )
		VALUES ( %d, %s, %s )
	", 
        array(
		10, 
		$metakey, 
		$metavalue
	) 
) );
```


- Create an EntityManager class so you can do the following:
```

// actually executes the queries (i.e. the INSERT query)
$em->flush();

return new Response('Saved new product with id '.$product->getId());
```

- Smarter flush command that combines queries of a specific table into one query. 

- Following simple query commands:
```
->find()
->findBy()
->findOneBy()
```

- Associations:
https://symfony.com/doc/current/doctrine/associations.html


# -

A lightweight, Doctrine style ORM for Wordpress 4.8+. Requires PHP 5.5.9+

This library borrows a lot of concepts from Doctrine for Symfony and applies them to Wordpress. Unlike Doctrine, this
library has VERY limited capabilities.
You can persist models to the db, modify the db schema based on the Model class, remove rows from the db and perform
some queries.

That's pretty much it. 


## Why?

I came to Wordpress from a Symfony development background. I looked around for a good ORM and was unable to find
anything that fullfilled my requirements. 

Using Doctrine in Wordpress was complex and seemed like overkill as it's a very heavy weight library. 

There's also: https://github.com/tareq1988/wp-eloquent which is really good, but is more of a query builder for
existing Wordpress tables.


## Installation

```
composer require rjjakes/wordpress-orm
```

## Usage

### Create a model

To use the ORM, first create a pure class that extends the ORM base model and add a number of properties as protected
variables like so:

```
<?php

namespace App\Models;

use Symlink\ORM\BaseModel as Model;

/**
 * @ORM_Type Entity
 * @ORM_Table "product"
 * @ORM_UUID false
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

`ORM_UUID` Optionally adds a UUID field to the table. This field will contain a unique UUID/4 string for each row. This
was required for my own project, so I have included it here. Unless you really need or want this feature, you probably
want to set this field to: False.
   
`ORM_AllowSchemaUpdate` This allows or blocks a database schema update with the updateSchema() function (see below).
For your custom models, you want to set this to True. When mapping models to existing Wordpress tables (such as
wp_posts), this should be set to False to avoid corrupting that table.      

**The property definitions are as follows:**

`ORM_Column_Type` The MySQL column type.
`ORM_Column_Length` The MySQL column length.
`ORM_Column_Null` The MySQL column NULl setting. (The only valid option here is 'NOT NULL'). For NULL, just omit this
annotation. 


### Update the database schema

Once you have a model object, you need to tell Wordpress to create a table to match your model. 

Use the mapper class:

```
use Symlink\ORM\Mapping;
```

First, get an instance of the ORM mapper object. This static function below makes sure `new Mapping()` is called once
per request. Any subsequent calls to `::getMapper()` will return the same object. This means you don't need to
continually create a new instance of Mapper() and parse the annotation.     

```
$mapper = Mapping::getMapper();
```

Now update the database schema for this class as follows: 

```
$mapper->updateSchema(Product::class);
```

This function uses the internal Wordpress dbDelta system to compare and update database tables. If your table doesn't
exist, it will be created, otherwise it checks the schema matches the model and modifies the database table if needed.
 
You should only run this function either when your plugin is activated or during development when you know you have
made a change to your model schema and want to apply it to the database 

### Persisting objects to the database. 

Use the ORM manager: 

```
use Symlink\ORM\Manager;
```

Create a new instance of your model:

```
$campaign = new Product();
$campaign->set('title', 'Some title');
$campaign->set('time', '2017-11-03 10:04:02');
$campaign->set('views', 34);
$campaign->set('short_name', 'something_here');
```

Get an instance of the ORM manager class. Like the Mapping class, this static function returns
a reusable instance of the manager class. 

```
$orm = Manager::getManager();
```

Now queue up these changes to apply to the database. Calling this does NOT immediately apply the changes to the
database. The idea here is the same as Doctrine: you can queue up many different changes to happen and once you're
ready to apply them, the ORM will combine these changes into as few queries as possible. 

This function uses the internal Wordpress `$wpdb->prepare()` function to protect against SQL injection attacks. 

```
$orm->persist($campaign);
```

Once, you're ready to apply all changes to your database (syncing what you have persisted to the database), call
flush():

```
$orm->flush();
```

Now check your database and you'll see a new row containing your model data. 

### Querying the database

x

## Pre-defined models

Wordpress ORM comes built in with several models that map to default Wordpress tables.

```
Symlink\Models\Post
Symlink\Models\Page
Symlink\Models\User
```
