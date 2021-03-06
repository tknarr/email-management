    Postfix/Dovecot e-mail system installation
    Copyright 2015 Todd Knarr
    Licensed under the terms of the GPL v3.0 or any later version
    See the LICENSE file for complete terms
    
I needed an e-mail setup where I could have users with a full shell account receive
mail through the usual Unix process with mail under their home directory, .forward
and procmail and such available, and so on. I also wanted to be able to set up
email-only accounts for friends without having to give them a full Unix shell
account they had no interest in in the process. I wanted it to be semi-automatic,
and I wanted to be able to manage/maintain it mostly through a Web interface. This
is the result. It uses MySQL for the database, Postfix and Dovecot for mail services,
and PHP for the Web interface.

The instructions are in the 'email-using-postfix-dovecot-mysql.md' document. I
recommend also having a backup mailserver in case the primary's offline, and the
document has instructions for that too.
