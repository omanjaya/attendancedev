# Production stage - using pre-built dist
FROM nginx:alpine
RUN apk add --no-cache curl
COPY dist /usr/share/nginx/html
COPY public/models /usr/share/nginx/html/models
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
