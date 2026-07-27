FROM node:22-alpine AS dev

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

EXPOSE 5173

# --host is needed for the port to be reachable from outside the container.
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
