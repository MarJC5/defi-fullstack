# Development stage
FROM node:20-alpine AS dev

WORKDIR /app

EXPOSE 3000

# Install dependencies and start dev server
# Dependencies are installed at runtime since source is mounted
CMD ["sh", "-c", "npm install && npm run dev"]

# Build stage
FROM node:20-alpine AS build

WORKDIR /app

COPY package*.json ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

COPY . .
RUN npm run build

# Production stage - copy build to volume
FROM node:20-alpine AS prod

WORKDIR /output

COPY --from=build /app/dist ./dist

# Script to copy files to mounted volume and exit
CMD ["sh", "-c", "if [ -d /target ]; then rm -rf /target/* && cp -r /output/dist/* /target/; fi; echo 'Build copied to volume'; exit 0"]
