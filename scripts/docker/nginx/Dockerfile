FROM nginx:alpine

# Install certbot for Let's Encrypt
RUN apk add --no-cache certbot certbot-nginx

# Copy custom nginx configuration
COPY conf.d/default.conf /etc/nginx/conf.d/default.conf

# Create SSL directory
RUN mkdir -p /etc/nginx/ssl

# Security headers and optimizations
RUN echo "# Security Headers" > /etc/nginx/conf.d/security.conf && \
    echo "add_header X-Frame-Options \"SAMEORIGIN\" always;" >> /etc/nginx/conf.d/security.conf && \
    echo "add_header X-Content-Type-Options \"nosniff\" always;" >> /etc/nginx/conf.d/security.conf && \
    echo "add_header X-XSS-Protection \"1; mode=block\" always;" >> /etc/nginx/conf.d/security.conf && \
    echo "add_header Referrer-Policy \"no-referrer-when-downgrade\" always;" >> /etc/nginx/conf.d/security.conf

# Expose ports
EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]
