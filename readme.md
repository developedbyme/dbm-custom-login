Custom login for Dbm content

# API

## Data structures

### User

```
{
	id: int,
	permalink: url,
	name: string,
	gravatarHash: string
}
```

### UserWithPrivateData extends User

```
{
	id: int (from User),
	permalink: url (from User),
	firstName: string,
	lastName: string,
	name: string (from User),
	email: string,
	gravatarHash: string (from User)
}
```

## Endpoints

### Login

Logs in the user in and sets the cookie that controlls if the user is logged in.

#### Request

POST `/wp-json/wprr/v1/action/login`

```
{
	log: string,
	pwd: string,
	remember: boolean
}
```

#### Response

```
{
	code: "success",
	data: {
		authenticated: boolean,
		user: UserWithPrivateData,
		roles: array of string,
		restNonce: string,
		restNonceGeneratedAt: unix timestamp
	}
}
```

#### Errors

HTTP 500 if the authentication doesn't go through

### Logout

#### Request

POST `/wp-json/wprr/v1/action/logout`

```
{}
```

#### Response

```
{
	code: "success",
	data: {
		authenticated: false,
		loggedOutUser: int
	}
}
```

### Has user

Check if an email is registered as a user

#### Request

POST `/wp-json/wprr/v1/action/has-user`

```
{
	email: string
}
```

#### Response

```
{
	code: "success",
	data: {
		hasUser: boolean,
		[userId: int]
	}
}
```

### Register user

Registers a user according to a specified method. Based on the method and the server settings the server might run the login directly after registration.

#### Request

POST `/wp-json/wprr/v1/action/register-user`

```
{
	method: string (default: "default")
	email: string,
	password: string,
	firstName: string,
	lastName: string,
	[additional verification data]
	[additional meta data]
}
```

#### Response

```
{
	code: "success",
	data: {
		registered: boolean,
		[verified: boolean],
		[alreadyRegistered: true],
		[userId: int],
		[user: UserWithPrivateData],
		[roles: array of string],
		[restNonce: string],
		[restNonceGeneratedAt: unix timestamp]
	}
}
```

#### Errors

Note that the call can give you a code "success" and registered false, which means that the registration did not get through for some reason.
