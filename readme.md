Gitlab access checker
=======

Built on top of Nette Framework, using Guzzle and some static assets.

## Prerequisites
- Docker

## Run
1. Clone repo
2. Copy `.env.example` to `.env`
3. Change Gitlab token in `.env`
4. Run `docker compose up -d`
5. Install packages from composer.json with `docker exec gitlab-access-checker-app composer install`
6. Enter `http://localhost:8088`