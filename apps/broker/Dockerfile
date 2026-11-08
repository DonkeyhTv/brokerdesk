# Production only: development runs the Vite server from docker-compose.yml.
FROM node:24-slim AS build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
ARG VITE_PORTAL_URL
ENV VITE_PORTAL_URL=$VITE_PORTAL_URL
RUN npm run build

# Caddy serves the static files and passes API calls on, so that the back office and the API share an origin.
FROM caddy:2-alpine

COPY --from=build /app/dist /srv/broker
