# SHOP-HAVEN Symfony API

This documentation covers the usage of the API developed with Symfony. The API manages products, categories, and allows various filters for searches.

## Table of Contents

- [SHOP-HAVEN Symfony API](#shop-haven-symfony-api)
  - [Table of Contents](#table-of-contents)
  - [Installation](#installation)
  - [Configuration](#configuration)
  - [API Routes](#api-routes)
    - [Users](#users)
    - [Products](#products)
    - [Categories](#categories)
    - [Images](#images)
    - [Authentication](#authentication)
      - [Roles](#roles)
    - [Documentation](#documentation)
  - [Usage Examples](#usage-examples)
  - [Contributing](#contributing)
  - [License](#license)

## Installation


1. Clone the repository:

   ```bash
   git clone -b backend https://github.com/whitecodename/SHOP-HAVEN
   ```

2. Navigate to the project directory:

   ```bash
   cd SHOP-HAVEN
   ```

3. Install the dependencies with Composer:

   ```bash
   composer install
   ```

4. Configure your database in the `.env` file. Fill the database informations (DBMSname, username, password, dbname):

   ```dotenv
   DATABASE_URL="DBMSname://username:password@127.0.0.1:3306/dbname"
   ```

5. Create the database tables:

   ```bash
   php bin/console doctrine:migrations:migrate
   ```

6. Start the development server:

   ```bash
   symfony server:start
   ```

## Configuration

Make sure you have configured the following files:

- `.env`: Environment and database variable settings.
- `config/packages/security.yaml`: Security and user role settings.

## API Routes

### Users

- **GET /api/users**: Retrieves all users.

- **GET /api/users/{id}**: Retrieves details of a specific user by ID.

- **POST /api/register**: Registers a new user.
  - JSON data example:
    ```json
    {
      "username": "johndoe",
      "email": "johndoe@example.com",
      "password": "securepassword",
      "roles": ["ROLE_USER"]
    }
    ```

- **PATCH /api/users/{id}**: Updates an existing user.

- **DELETE /api/users/{id}**: Deletes a user if he's the one connected.

### Products

- **GET /api/products**: Retrieves all products or filters based on criteria.
  - Possible query parameters: `category`, `minPrice`, `maxPrice`, `minQuantity`, `maxQuantity`.
  
- **GET /api/products/{id}**: Retrieves details of a specific product by ID.

- **POST /api/products**: Creates a new product.
  - JSON data example:
    ```json
    {
      "name": "Product Name",
      "description": "Product Description",
      "price": 20.99,
      "quantity": 50,
      "category": {
        "id": 1
      }
    }
    ```

- **PATCH /api/products/{id}**: Updates an existing product.

- **DELETE /api/products/{id}**: Deletes a product by ID.
  
### Categories

- **GET /api/categories**: Retrieves all categories.

- **GET /api/categories/{id}**: Retrieves details of a specific category by ID.

- **POST /api/categories**: Creates a new category.
  - JSON data example:
    ```json
    {
      "name": "Category Name"
    }
    ```

- **PATCH /api/categories/{id}**: Updates an existing category.

- **DELETE /api/categories/{id}**: Deletes a category by ID.

### Images

- **GET /api/images**: Retrieves all images.

- **GET /api/images/{id}**: Retrieves details of a specific image by ID.

- **POST /api/{id}/images**: Upload a new image for a specific product referenced by id.
  - Form Data example:
    ```Form Data
    thumbnail: File
    ```
  - Headers:
    ```bash
    Content-Type: multipart/form-data
    ```

- **PATCH /api/{id}/images**: Updates an existing category.
  - Form Data example:
    ```Form Data
    thumbnail: File
    ```
  - Headers:
    ```bash
    Content-Type: multipart/form-data
    ```

- **DELETE /api/images/{id}**: Deletes a category by ID.

### Authentication

This API uses token-based authentication for securing endpoints. Make sure to include your token in the `Authorization` header as a `Bearer` token for requests that require authentication. This token is given once login done.

```bash
Authorization: Bearer <login-token-here>
```

#### Roles
This API covers four types of roles : user, edit_1, edit_2, admin. Note that everyone can use authentification : register, login.

- **user(`ROLE_USER`)**:
  - **Access:** Read-only
  - **Routes:**
    - **Products:** `GET` routes
    - **Categories:** `GET` routes
    - **Images:** `GET` routes
    - **Users:** Register, Update or Delete his own account
- **edit_1(`ROLE_EDIT_1`)**:
  - **Access:** Read, Update images, products
  - **Routes:**
    - **Products:** Full access
    - **Categories:** `GET` routes
    - **Images:** Full access
    - **Users:** Register, Update or Delete his own account
- **edit_2(`ROLE_EDIT_2`)**:
  - **Access:** Read, Update images, products and categories
  - **Routes:**
    - **Products:** Full access
    - **Categories:** Full access
    - **Images:** Full access
    - **Users:** Register, Update or Delete his own account
- **admin(`ROLE_ADMIN`)**:
  - **Access:** Read, Update, View users.
  - **Routes:**
    - **Products:** Full access
    - **Categories:** Full access
    - **Images:** Full access
    - **Users:** View, Show, Change roles.


### Documentation

This readme plays the role of the API documentation. For a simple list of the differents API routes, use the **GET /api/doc** route.

## Usage Examples

To interact with the API, you can use tools like `curl`, Postman, Insomnia, or integrate it into your client application.

Example request to retrieve products with price filters:

```bash
curl -X GET "http://localhost:8000/api/products?category=1&minPrice=10&maxPrice=50" -H "accept: application/json"
```

## Contributing

Contributions are welcome! Please submit an issue or a pull request for any improvements.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.