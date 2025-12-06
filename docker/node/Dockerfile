# ============================================
# Node.js + React Frontend (Production Build)
# ============================================

# Stage 1: Build
FROM node:20-alpine AS builder

WORKDIR /app

# Copy package files
COPY frontend/package*.json ./

# Install dependencies
RUN npm ci

# Copy source code
COPY frontend/ .

# Build for production
ARG VITE_API_BASE_URL
ARG VITE_DEEPFACE_URL
ENV VITE_API_BASE_URL=$VITE_API_BASE_URL
ENV VITE_DEEPFACE_URL=$VITE_DEEPFACE_URL

RUN npm run build

# Stage 2: Serve with nginx
FROM nginx:alpine

# Copy built files
COPY --from=builder /app/dist /usr/share/nginx/html

# Copy nginx configuration
COPY docker/node/nginx.conf /etc/nginx/conf.d/default.conf

# Expose port
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
