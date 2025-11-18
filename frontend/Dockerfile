# Development stage
FROM node:20-alpine AS dev

WORKDIR /app

EXPOSE 3000

# Install dependencies and start dev server
# Dependencies are installed at runtime since source is mounted
CMD ["sh", "-c", "if [ -f package-lock.json ]; then npm ci; else npm install; fi && npm run dev"]

# Build stage
FROM node:20-alpine AS build

WORKDIR /app

COPY package*.json ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

COPY . .
RUN npm run build

# Production stage - output built files
FROM scratch AS prod

COPY --from=build /app/dist /dist
