# Building a Microblogging Platform

This tutorial demonstrates how to use DBAL to create a small Twitter-like service. The goal is to implement tweets, comments, likes, following other users, basic moderation and sending notifications to external systems. Each section shows the relevant tables and typical queries.

## Database Schema

The following tables cover the basic features:

- `users` – user accounts
- `tweets` – short messages authored by users
- `comments` – replies to tweets
- `likes` – references between users and tweets they like
- `follows` – links between followers and the accounts they follow
- `reports` – moderation queue for problematic content
- `notifications` – outbound messages for external systems

### Creating tables
```php
$crud   = new DBAL\Crud($pdo);
$schema = new DBAL\SchemaMiddleware($pdo);
$crud   = $crud->withMiddleware($schema);

// users
$crud->createTable('users')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('username', 'TEXT UNIQUE')
    ->column('email', 'TEXT')
    ->column('created_at', 'TEXT')
    ->execute();

// tweets
$crud->createTable('tweets')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('user_id', 'INTEGER')
    ->column('body', 'TEXT')
    ->column('created_at', 'TEXT')
    ->execute();

// comments
$crud->createTable('comments')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('tweet_id', 'INTEGER')
    ->column('user_id', 'INTEGER')
    ->column('body', 'TEXT')
    ->column('created_at', 'TEXT')
    ->execute();

// likes
$crud->createTable('likes')
    ->column('user_id', 'INTEGER')
    ->column('tweet_id', 'INTEGER')
    ->execute();

// follows
$crud->createTable('follows')
    ->column('follower_id', 'INTEGER')
    ->column('followed_id', 'INTEGER')
    ->execute();

// moderation reports
$crud->createTable('reports')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('tweet_id', 'INTEGER')
    ->column('user_id', 'INTEGER')
    ->column('reason', 'TEXT')
    ->execute();

// outgoing notifications
$crud->createTable('notifications')
    ->column('id', 'INTEGER PRIMARY KEY AUTOINCREMENT')
    ->column('type', 'TEXT')
    ->column('payload', 'TEXT')
    ->column('sent', 'INTEGER DEFAULT 0')
    ->execute();
```

## Posting Tweets
```php
$tweets = (new DBAL\Crud($pdo))->from('tweets');

$tweetId = $tweets->insert([
    'user_id'    => $userId,
    'body'       => $text,
    'created_at' => date('c'),
]);
```

## Reading the Timeline
```php
$timeline = $tweets
    ->leftJoin('users u', function ($on) {
        $on->{'tweets.user_id__eqf'}('u.id');
    })
    ->desc('tweets.created_at')
    ->select('tweets.*', 'u.username');
```

## Adding Comments and Likes
```php
$comments = (new DBAL\Crud($pdo))->from('comments');
$likes    = (new DBAL\Crud($pdo))->from('likes');

$comments->insert([
    'tweet_id'   => $tweetId,
    'user_id'    => $userId,
    'body'       => $reply,
    'created_at' => date('c'),
]);

$likes->insert([
    'tweet_id' => $tweetId,
    'user_id'  => $userId,
]);
```

## Following Users
```php
$follows = (new DBAL\Crud($pdo))->from('follows');
$follows->insert([
    'follower_id' => $userId,
    'followed_id' => $targetId,
]);
```

To list the accounts a user follows:
```php
$follows->where(['follower_id' => $userId])->select('followed_id');
```

## Moderation Queue
```php
$reports = (new DBAL\Crud($pdo))->from('reports');
$reports->insert([
    'tweet_id' => $tweetId,
    'user_id'  => $userId,
    'reason'   => 'spam',
]);
```

Moderators can review items:
```php
foreach ($reports->select() as $report) {
    // decide what to do with $report
}
```

## Sending Notifications
Use event hooks to store notifications for external systems:
```php
use DBAL\Hooks\{afterInsert};

$tweets = afterInsert($tweets, function ($row) use ($pdo) {
    $notify = (new DBAL\Crud($pdo))->from('notifications');
    $notify->insert([
        'type'    => 'tweet.created',
        'payload' => json_encode($row),
    ]);
});
```
External processes can poll the `notifications` table and dispatch events to other services.

## Summary

This example covers the essentials of a microblogging service using DBAL. The same approach can be extended with more complex validation, caching or any custom middleware. Consult the rest of the documentation for details on middlewares and integration techniques.

