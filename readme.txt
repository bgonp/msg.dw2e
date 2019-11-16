---
GET
---

recover (id, key)
confirm (id, key)

----
POST
----

update (chat_id, last_received, last_read, last_contact_upd)
	redirect
	usuario_id
	focus
	userdata
	chats
	messages
	friends
	requests
	members
	last_contact_upd

login (email, password)
	redirect

logout ()
	redirect

register (email, nombre, password, password_rep, avatar)
	redirect

resetSend (email)

resetPassword (id, key, password, password_rep)
	redirect

editProfile (email, name, password, password_rep, avatar)
	userdata

requestFriend (email, last_contact_upd)
	friends
	requests
	last_contact_upd
	
acceptFriend (contact_id, last_contact_upd)
	friends
	requests
	last_contact_upd

rejectFriend (contact_id)
	friends
	requests
	last_contact_upd

blockFriend (contact_id)
	friends
	requests
	last_contact_upd

createChat (name, members[], last_received)
	chats

leaveChat (chat_id)

loadChat (chat_id)
	id
	fecha
	nombre
	last_msg
	last_read
	messages
	members

addMember (chat_id, contact_id)
	members

sendMessage (chat_id, mensaje, last_read)
	messages

updateOptions (options[])
	redirect

installApp (email, password, password_rep, host, name, user, pass)
	redirect



