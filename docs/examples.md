# Practical Use Cases

Below are some scenarios where DBAL can simplify data access.

## Online Book Store
Use `Crud` to handle products and orders while middlewares provide validation and caching. Relations allow lazy loading of author data.

## Cinema Ticketing
Manage screenings and seat reservations with transactions and unit of work to ensure consistency.

## Logistics API in Microservices
Combine DBAL with Slim or Lumen to create lightweight services that read and write package data. Global filters can enforce multi-tenant restrictions across all queries.


