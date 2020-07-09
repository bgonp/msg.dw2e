# DW2E Instant Messaging System

### Table of contents
- [Description](#Description)
- [Features](#Features)
- [Requeriments](#Requeriments)
- [Installation](#Installation)
- [Settings](#Settings)
- [Usage](#Usage)


## Description

A responsive instant messaging single page application to talk about topics in chat rooms with other people. This project has been done as an assignment of Server Side Programming module.

## Features

- Access the application with your e-mail and password.
- If you don't have an account, you can create it. E-mail verification needed (disabled by default).
- If you don't remember your password, you can reset it by e-mail (disabled by default).
- Request friendship to other users with their e-mails.
- Create chat rooms and add some of your friends.
- Send messages to chat rooms with other users.
- Messages can contain attachment files or images.
- You can accept or decline friendship or block a friend.
- You can send messages to non-friends users if your are in the same chat room (but you can't see their avatars).
- You can modify your profile: name, e-mail, password and avatar.
- Uploaded avatars will be resized to a square image of maximum 200x200.

## Requeriments

- PHP >= 7.1
- Composer
- MySQL

## Installation

- Create in your server a MySQL database with a user who can use it.
- In the target folder clone this project `$ git clone https://github.com/bgonp/msg.dw2e.git .`
- Ensure the folders `config/` `upload/attachment/` and `upload/avatar/` have write permissions.
- Install needed packages `$ composer install`. Required packages are PHPMailer, jQuery, font-awesome, bootstrap.
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
- Colors of the app (main and auxiliary colors).
- Max file size of avatars and messages attachments.
- Enable e-mail confirmation at register and set e-mail credentials.

## Usage

#### Register
- Click on `Registration` tab.
- Enter your name, e-mail and password (twice). Optionally, you can also upload an avatar.
- If e-mail confirmation is enabled, you will receive an email with a button to confirm your account.
- If not, you will be automatically logged in.

#### Login
- Simply write your e-mail and password in the `Login` box.
- If you don't remember your password you can request a new one by clicking on the link below password box. This functionality will be available only if e-mail confirm option is enabled (see [settings](#Settings) section).

#### Logout
- Simply click on `Logout` button in the upper menu and confirm.

#### Profile
- In the upper menu, you can click on `My profile` button to edit your personal info.
- If you change your e-mail and e-mail confirm option is enabled, you will have to re-confirm your account. Otherwise you won't be able to login again once you logout.

#### Friends
- In the left sidebar, click  on `Friends` tab button to see your friends list.
- Here you can request friendship with someone by introducing your friend's e-mail and clicking on `Add` button.
- In the left sidebar, click on `Requests` tab button to see your received friendship requests.
- If someone requests your friendship here you can `Accept` or `Decline` it.
- Once you accept a request or someone accept your request, you can see this friend in the friends-list.
- If you want to block a friend, click on the forbidden icon at the bottom-right corner of your friend box. A blocked friend won't be able to send you friendship requests or create chat rooms with you. But you will continue receiving their message in any chat room already created with both of you.

#### Chats
- If you want to create a chat room you must first select one or more friends in your friends list by clicking on the checkbox of each one. Then you have to introduce in the inbox below the new chat room name and click on `New chat` button.
- You will be redirected to chats list and here you can see the new chat room. Click on it and you can start writing here.
- If you want to add a friend to the current chat room, select him it in the `Add a friend...` box and click on the `+` icon.
- If you want to leave a chat room, click on the forbidden icon at the right of the chat room and confirm.
- You can also filter chat rooms by using the filter input here. This is useful if you belongs to several chat rooms.

#### Messages
- Once you are in a chat room you can see at the left who are in this room and will be able to see all the messages here. Also, you can see their avatars in the upper side of the messages section (if someone in the room is not your friend, you won't see his avatar, but you can see his messages and viceversa).
- To write a message simply write it in the text box below and click on `Send` button. Max 1000 characters.
- Addionally, you can attach a file to your message by clicking on the paperclip button. If this file is an image you will see a preview of it once you send the message. If it isn't an image, you will see an icon in your message to download the file.
- You can't upload a file if you don't write anything in the message box. A message text will be always needed.

## Full Documentation

You can download full documentation PDF [**here**](https://github.com/bgonp/msg.dw2e/raw/master/doc/DW2E%20MSG.pdf).

## Database Schema

![Database schema](https://raw.githubusercontent.com/bgonp/msg.dw2e/master/doc/db_schema.png)

## Screenshots

### Installation
![Installation](https://raw.githubusercontent.com/bgonp/msg.dw2e/master/doc/02_install_fill.png)

### Options
![Options](https://raw.githubusercontent.com/bgonp/msg.dw2e/master/doc/05_options.png)

### Registration
![Registration](https://raw.githubusercontent.com/bgonp/msg.dw2e/master/doc/07_register_fill.png)

### Main screen with friends
![Friends](https://raw.githubusercontent.com/bgonp/msg.dw2e/master/doc/12_friends.png)

### Main screen with chat and attachments
![Chat](https://raw.githubusercontent.com/bgonp/msg.dw2e/master/doc/16_chat_attachments.png)
