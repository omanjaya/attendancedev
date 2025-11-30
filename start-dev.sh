#!/bin/bash

# Unset conflicting environment variables that prevent .env loading
unset APP_KEY APP_NAME APP_ENV APP_DEBUG APP_URL APP_TIMEZONE

# Start all development services
composer dev
