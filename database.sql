/*
CREATE DATABASE IF NOT EXISTS api_rest_laravel;
USE api_rest_laravel;

CREATE TABLE users(
id              int(255) auto_increment not null,
name            varchar(50) not null,
surname         varchar(100) ,
role            varchar(20),
email           varchar(255) not null,
password        varchar(255) not null,
description     text,
image           varchar(255),
created_at      datetime DEFAULT NULL,
updated_at      datetime DEFAULT NULL,
remember_token  varchar(255),
CONSTRAINT pk_users PRIMARY KEY(id)
)ENGINE=InnoDB;

CREATE TABLE categories(
id              int(255) auto_increment not null,
name            varchar(100) not null,
created_at      datetime DEFAULT NULL,
updated_at      datetime DEFAULT NULL,
CONSTRAINT pk_categories PRIMARY KEY(id)
)ENGINE=InnoDb;

CREATE TABLE posts(
id              int(255) auto_increment not null,
user_id         int(255)not null,
category_id     int(255)not null,
title           varchar(255) not null,
content         text not null,
image           varchar(255),
created_at      datetime DEFAULT NULL,
updated_at      datetime DEFAULT NULL,
CONSTRAINT pk_posts PRIMARY KEY(id)
CONSTRAINT fk_posts_user FOREIGN KEY(user_id) REFERENCES users(id),
CONSTRAINT fk_posts_category FOREIGN KEY(category_id) REFERENCES categories(id)
)ENGINE=InnoDb;
*/
CREATE DATABASE IF NOT EXISTS api_rest_laravel_admin;
USE api_rest_laravel_admin;



CREATE TABLE categories(
id              int(255) auto_increment not null,
name            varchar(100) not null,
created_at      datetime DEFAULT NULL,
updated_at      datetime DEFAULT NULL,
CONSTRAINT pk_categories PRIMARY KEY(id)
)ENGINE=InnoDb;

CREATE TABLE posts(
id              int(255) auto_increment not null,
category_id     int(255)not null,
title           varchar(255) not null,
content         text not null,
image           varchar(255),
created_at      datetime DEFAULT NULL,
updated_at      datetime DEFAULT NULL,
CONSTRAINT pk_posts PRIMARY KEY(id),
CONSTRAINT fk_posts_category FOREIGN KEY(category_id) REFERENCES categories(id)
)ENGINE=InnoDb;

CREATE TABLE images(
id              int(255) auto_increment not null,
post_id         int(255)not null,
image_name      varchar(255) not null,
description     varchar(255) DEFAULT '',
created_at      datetime DEFAULT NULL,
updated_at      datetime DEFAULT NULL,
CONSTRAINT pk_images PRIMARY KEY(id),
CONSTRAINT fk_images_post FOREIGN KEY(post_id) REFERENCES posts(id)
)ENGINE=InnoDb;