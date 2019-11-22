# DW2E Instant Messaging System

[TOC]

## Description

An instant messaging single page application to talk about topics in chat rooms with other people.

## Features

- Access the application with your e-mail and password.
- If you don't have an account, you can create it. E-mail verification needed (disabled by default).
- If you don't remember your password, you can reset it by e-mail (disabled by default).
- Request friendship to other users with their e-mails.
- Create chat rooms and add some of your friends.
- Send messages to chat rooms with other(s) user(s).
- Messages can contain attachment file or images.
- You can accept or decline friendship or block a friend.
- You can send messages to non-friends users if your are in the same chat room (but you can't see their avatars).
- You can modify your profile: name, e-mail, password and avatar.

## Requeriments

- PHP >= 7.2
- Composer
- MySQL

## Installation

- Create in your server a MySQL database with an user who can use it.
- In the target folder clone this project `$ git clone https://github.com/bgonp/msg.dw2e.git .`
- Ensure the folders `upload/attachment` and `upload/avatar` have write permissions.
- Install needed packages `$ composer install`
- Visit the url where the app has been installed to see the installation menu.
- Here you have to introduce your e-mail and password in order to create the admin user.
- In addition you need to introduce the information to access the database.
- Once you saved this data, you can access the admin panel through your email and password.
- In the admin panel you can configure more settings (see [settings](#Settings) section).
- **That's all**. Now people can sign up and use the app.

## Settings

The admin user can modify some settings from the control panel:

- Password requisites with a regex for security reasons.
- Name of users and chats requisites with a regex.
- E-mail conditions with a regex to, for example, limit registration to a certain domain.
- Colors of the app (main color, background and borders color).
- Max file size of avatars and messages attachments
- Enable e-mail confirmation at register and set e-mail credentials.

## Usage