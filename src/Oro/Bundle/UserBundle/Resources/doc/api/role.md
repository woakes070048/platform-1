# Oro\Bundle\UserBundle\Entity\Role

## ACTIONS  

### get

Retrieve a specific user role record.

{@inheritdoc}

### get_list

Retrieve a collection of user role records.

{@inheritdoc}

### create

Create a new user role.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "userroles",
      "attributes": {
         "role": "ROLE_GUEST",
         "label": "Guest"
      },
      "relationships": {
         "organization": {
            "data": {
               "type": "organizations",
               "id": "1"
            }
         }
      }
   }
}
```
{@/request}

### update

Edit a specific user role record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "userroles",
      "id": "10",
      "attributes": {
         "label": "Guest"
      }
   }
}
```
{@/request}

### delete

Remove a specific user role.

{@inheritdoc}

### delete_list

Delete a collection of user roles.

{@inheritdoc}

## FIELDS

### role

#### create

{@inheritdoc}

**Note**: The submitted value is just a recommended prefix. It is used to generate unique code for the role.

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

**Note**: The submitted value is just a recommended prefix. It is used to generate unique code for the role.

### label

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

## SUBRESOURCES

### organization

#### get_subresource

Retrieve a record of the organization that a specific user role belongs to.

#### get_relationship

Retrieve the ID of the organization that a specific user role belongs to.

#### update_relationship

Replace the organization that a specific user role belongs to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "organizations",
    "id": "1"
  }
}
```
{@/request}

### users

#### get_subresource

Retrieve records of users who are assigned to a specific user role record.

#### get_relationship

Retrieve the IDs of the users who are assigned to a specific user role record.

#### add_relationship

Assign the user records to a specific user role record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "users",
      "id": "1"
    },
    {
      "type": "users",
      "id": "2"
    },
    {
      "type": "users",
      "id": "3"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the user records that are assigned to a specific user role record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "users",
      "id": "1"
    },
    {
      "type": "users",
      "id": "2"
    },
    {
      "type": "users",
      "id": "3"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove user records from a specific user role record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "users",
      "id": "1"
    }
  ]
}
```
{@/request}
