#!/usr/bin/env bash
touch .env
echo "MYSQL_USER=root" >> .env
echo "MYSQL_ROOT_PASSWORD=admin" >> .env
echo "MYSQL_DATABASE=test" >> .env
echo "MYSQL_HOST=db" >> .env
echo "MYSQL_PORT=3306" >> .env