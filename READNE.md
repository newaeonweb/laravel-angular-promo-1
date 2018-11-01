# Laravel Restful API using Docker in three steps.

In this post serie we will see how to set up an environment for developing Laravel applications using **Docker** and **Docker-compose**. In addition we will see the basic functionalities of **Eloquent ORM** and some relationships between Models. Our goal is to build a **Restful API**.

> This post was inpired by the book: [Hands-On Full Stack Web Development with Angular 6 and Laravel 5](https://www.packtpub.com/web-development/hands-full-stack-web-development-angular-6-and-laravel-5), from [Fernando Monteiro](http://newaeonweb.com.br/about/), released on July/2018 by [Packt](https://www.packtpub.com/).

So let's split this tutorial in three steps. Let's see the first step right now:

# 1. Preparing the Docker/Docker-compose environment.
First we are going to create the foundation (Dockerfile, Docker-compose) files for the application.

1. Create a new folder on your machine.
2. Inside the  newly created folder, create another folder called **docker**.
3. Inside the **docker** folder, add two folders, one called: **nginx** and another called: **php-fpm**.

## Setting up Docker, MySQL, Nginx and PHP.

1. Inside the **nginx** folder add the following file called: `nginx.conf` with the following code:

```
server {
    listen 80 default;

    client_max_body_size 308M;

    access_log /var/log/nginx/application.access.log;


    root /application/public;
    index index.php;

    if (!-e $request_filename) {
        rewrite ^.*$ /index.php last;
    }

    location ~ \.php$ {
        fastcgi_pass php-fpm:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PHP_VALUE "error_log=/var/log/nginx/application_php_errors.log";
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        include fastcgi_params;
    }
}
```

2. Inside the **php-fpm** folder add the following file called: `Dockerfile` with the following code:

```
FROM phpdockerio/php72-fpm:latest
WORKDIR "/application"

# Install selected extensions and other stuff
RUN apt-get update \
    && apt-get -y --no-install-recommends install  php7.2-mysql php-xdebug libmcrypt-dev \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
```

3. Now inside the **root-project** folder add the following file called: `docker-compose.yml` with the following code:

```
version: "3.1"
services:

  mysql:
    image: mysql:5.7
    container_name: mysql
    working_dir: /application
    volumes:
      - .:/application
      - ./storage-db:/var/lib/mysql
    environment:
      - MYSQL_ROOT_PASSWORD=123456
      - MYSQL_DATABASE=databasename
      - MYSQL_USER=databaseuser
      - MYSQL_PASSWORD=123456
    ports:
      - "8083:3306"

  webserver:
    image: nginx:alpine
    container_name: webserver
    working_dir: /application
    volumes:
     - .:/application
     - ./docker/nginx/nginx.conf:/etc/nginx/conf.d/default.conf
    ports:
     - "8081:80"

  php-fpm:
    build: docker/php-fpm
    container_name: php-fpm
    working_dir: /application
    volumes:
      - .:/application
      - ./docker/php-fpm/php-ini-overrides.ini:/etc/php/7.2/fpm/conf.d/99-overrides.ini

```

> You can read more about the docker-compose.yml details on our book:


**A special note about the MySQL container configuration: The previous code setting the *storage-db* folder on our _project/machine_ to store all the MyQSL data from the MySQL container.**

The final structure will be the following:

``` php

project-folder
  /docker/
    /nginx/
      nginx.conf
    /php-fpm/
      Dockerfile
  docker-compose.yml

```

## Using Composer to create a Laravel application inside Docker container.
Now that we created a solid base on our servers. The PHP image we used already has all the dependencies that Laravel needs to run the application including **Composer**.

So we will use the Composer that we have inside the **php-fpm** container, this is the safest way to avoid conflicts between your machine/container Composer versions.

1. Open your Terminal window and type the following command:

  `docker-compose exec php-fpm bash`

2. Still on your Terminal window, type the following command:

  `composer create-project laravel/laravel=5.6.12 server --prefer-dist`

> At the time of this example, we installed version 5.6.12 of Laravel, although we should have no problem installing a more current version, we strongly recommend that you keep the version in 5.6. *.

**Note that we create the Laravel application inside a directory called `server`, this way we will have the following application structure:**

``` php

project-folder
    /docker/
    /server/
    docker-compose.yml
```

Notice that we have separated the contents of the Laravel application from the **Docker** configuration folder. This practice is highly recommended since we can make any kind of changes within the project folder without damaging any Docker or docker-compose files accidentally.

Now we need to adjust the `docker-compose.yml` file, to fit the new path created.

3. Open `docker-compose.yml` and let's adjust the **php-fpm** `volumes` tag with the new path, as the following block of code:

```php
    php-fpm:
      build: phpdocker/php-fpm
      container_name: php-fpm
      working_dir: /application
      volumes:
        - ./server:/application
        - ./phpdocker/php-fpm/php-ini-overrides.ini:/etc/php/7.2/fpm/conf.d/99-overrides.ini

```

For the change we just made take effect, we need to stop and restart our containers. Let's go to the next steps.

4. Still on your Terminal, type `exit` to exit the php-fpm bash.
5. Now at the root of application folder, type the following command:

  `docker-compose kill`

You must see the following output message:

``` console
Stopping webserver ... done
Stopping mysql     ... done
Stopping php-fpm   ... done
```

6. On your Terminal type the following command to run the containers again:

`docker-compose up -d`

And now we can see that **php-fpm** was recreated and now will reflect our changes:

```console
Recreating php-fpm ... done
Starting webserver ... done
Starting webserver ... done
```

> It is highly recommended that you repeat this procedure whenever you make any changes to the **nginx** and **php-fpm** servers.

Now let's check the Laravel instalation and configuration, open your default browser and go to the following link: http://localhost:8081/

Great! Congratulations you should see the Laravel welcome page.

## Configuring the `.env` file.

1. Open `.env` file at the root of **project** folder and replace the database config, for the following lines:

```
  DB_CONNECTION=mysql
  DB_HOST=mysql
  DB_PORT=3306
  DB_DATABASE=databasename
  DB_USERNAME=databaseuser
  DB_PASSWORD=123456
```

Let's check the connection.

2. Inside the Terminal window type the following command to go inside the **php-fpm bash**:

  `docker-compose exec php-fpm bash`

3. Inside the `php-fpm bash`, type the following command:

  `php artisan tinker`

4. And finally, type the folloiwng command: `DB::connection()->getPdo();`, you must see something similar to the following output:

```
=> PDO {#760
     inTransaction: false,
     attributes: {
       CASE: NATURAL,
       ERRMODE: EXCEPTION,
       AUTOCOMMIT: 1,
       PERSISTENT: false,
       DRIVER_NAME: "mysql",
       SERVER_INFO: "Uptime: 2491  Threads: 1  Questions: 9  Slow queries: 0  Opens: 105  Flush tables: 1  Open tables: 98  Queriesper second avg: 0.003",
       ORACLE_NULLS: NATURAL,
       CLIENT_VERSION: "mysqlnd 5.0.12-dev - 20150407 - $Id: 38fea24f2847fa7519001be390c98ae0acafe387 $",
       SERVER_VERSION: "5.7.21",
       STATEMENT_CLASS: [
         "PDOStatement",
       ],
       EMULATE_PREPARES: 0,
       CONNECTION_STATUS: "mysql via TCP/IP",
       DEFAULT_FETCH_MODE: BOTH,
     },
   }
```

This means everything goes well. Congratulation we have a database up and running.

## Creating Migrations and Database seed.
Now, the funny part will start right now, let's create a Model.

> Remember you should running every `Artisan` command inside the **php-fpm** bash to avoid compatibility issues.

1. Open your Terminal window inside **your-project** folder and type the following command:

`docker-compose exec php-fpm bash`

2. Inside bash, type the following command:

`php artisan make:model Band -m`

Note the flag `-m` that we are using to create the migration file together with the Model creation. So now we have two new files on our Aplication.

* `server/app/Band.php`
* `server/database/migrations/###_##_##_######_create_bands_table.php`

### Creating the migration boilerplate
The newly created files only have the boilerplate code generated by Laravel engine. Let's add some content to Band Model and migration file.

3. Open `server/app/Band.php` and add the following code, inside the Band Model function:

```php

    protected $fillable = [
        'name',
        'country',
        'genre'
    ];

```

4. Now we need to add the same properties to the **migration** file created before. Open `server/database/migrations/####_##_##_######_create_bands_table.php` and add the following properties inside the `up()` function:

```php

    $table->string('name');
    $table->string('country');
    $table->string('genre');

```

Well, congratulations we created our first migration file and it is time to execute the command to feed our database.

5. Open your Terminal window and type the following command:

`php artisan migrate`

The output of the previous command will be similar to the following:

```
Migration table created successfully.
Migrating: ####_##_##_######_create_users_table
Migrated:  ####_##_##_######_create_users_table
Migrating: ####_##_##_######_create_password_resets_table
Migrated:  ####_##_##_######_create_password_resets_table
Migrating: ####_##_##_######_create_bands_table
Migrated:  ####_##_##_######_create_bands_table
```

### Creating our first database seed using a JSON file

Instead use the popular Faker library to create our data, we will use an external JSON file with the data we want to insert into our database.
Although Faker is a very useful tool, easy and fast to create data during the development of applications with Laravel. In this example we want to keep the control over the data created.

1. Inside the `server/database` folder, create a new folder called: **data-sample**.
2. Inside the `server/database/data-sample` folder, create a new file called: `bands.json` and add the following code:

```json

[{
	"id": 1,
	"name": "Motorhead",
	"country": "England",
	"genre": "Heavy Metal"
}, {
	"id": 2,
	"name": "Slayer",
	"country": "USA",
	"genre": "Thrash Metal"
}, {
	"id": 3,
	"name": "Truckfighters",
	"country": "Sweeden",
	"genre": "Stoner"
}]

```

3. Now it's time to create our seed file, on your Terminal window type the following command:

`php artisan make:seeder BandsTableSeeder`

The previous command added a new file called: **BandsTableSeeder.php** inside: `server/database/seeds` folder.

4. Open `server/database/seeds/BandsTableSeeder.php` and replace the code for the following block of code:

```php

  use Illuminate\Database\Seeder;
  use App\Band;

  class BandsTableSeeder extends Seeder
  {
      /**
       * Run the database seeds.
       *
       * @return void
      */
      public function run()
      {
          DB::table('bands')->delete();
          $json = File::get("database/data-sample/bands.json");
          $data = json_decode($json);
          foreach ($data as $obj) {
            Bike::create(array(
                'id' => $obj->id,
                'name' => $obj->name,
                'country' => $obj->country,
                'genre' => $obj->genre
            ));
          }
      }
  }

```

Note the first line we using the Eloquent ORM shortcut `(DB::table())` to previously delete the bands tables if exist. On next post we go deeper on Eloquent ORM, for now let's focus on create our first seed.

5. Open `server/database/seeds/DatabaseSeeder.php` and add the following line of code, right after the `UsersTableSeeder` comment:

```php
  $this->call(BikesTableSeeder::class);
```

> You can read more about **Eloquent ORM** at [chapter 5 - Creating a Restful API using Laravel Framework (Part I)](https://github.com/newaeonweb/Hands-On-Full-Stack-Web-Development-with-Angular-6-and-Laravel-5#chapter-5-creating-a-restful-api-using-laravel-framework-part-i).

Now is time to running our seed and fill the database. We can do this in two ways, we can just run the **BandSeeder** command: `php artisan db:seed --class=BandsTableSeeder` individually or use the command: `php artisan db:seed` that will run all seeds in our project.

As we are at the beginning of our development we will execute the command to load all the seeds.

6. Open your Terminal window and type the following command:

  `php artisan db:seed`

At the end of the previous command we see a success message **Seeding: BandsTableSeeder**, Bravo! now we have our first records on our database. Let's check the records.

7. Still on you terminal window, type the `tinker` command:

  `php artisan tinker`

8. Now let's check our database table with the following command:

  `DB::table('bands')->get();`

The output on terminal window will be something like a JSON representing the three records with our bands.

> This post was inpired by the book: [Hands-On Full Stack Web Development with Angular 6 and Laravel 5](https://www.packtpub.com/web-development/hands-full-stack-web-development-angular-6-and-laravel-5), from [Fernando Monteiro](http://newaeonweb.com.br/about/), released on July/2018 by [Packt](https://www.packtpub.com/).

Continues on the next post...
