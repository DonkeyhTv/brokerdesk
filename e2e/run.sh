#!/bin/sh
# Inside this container, localhost must answer like on a developer machine:
# the back office only accepts sessions from http://localhost:5173.
set -e

socat TCP-LISTEN:5173,fork,reuseaddr TCP:broker:5173 &
socat TCP-LISTEN:3000,fork,reuseaddr TCP:portal:3000 &
socat TCP-LISTEN:8000,fork,reuseaddr TCP:nginx:80 &
socat TCP-LISTEN:8025,fork,reuseaddr TCP:mailpit:8025 &

npm ci --no-audit --no-fund

for url in http://localhost:8000/api/v1/health http://localhost:5173/login http://localhost:3000/; do
  timeout 300 sh -c "until curl -fs -o /dev/null $url; do sleep 3; done"
done

exec npx playwright test "$@"
