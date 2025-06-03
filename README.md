Project Setup=
1) Copy .env.example to .env 
2) Run command: "composer install" = this will install all packages
3) Create a database named "roster" in mysql using phpmyadmin
4) Run command: "php artisan migrate" = this will create all tables
5) Run command: "php artisan serve --port=8000" = This will run the project locally on port 8000
6) Use roster.postman_collection.json for Postman collection of APIs