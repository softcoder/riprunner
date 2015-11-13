import logging, email, urllib
from google.appengine.ext import webapp 
from google.appengine.ext.webapp.mail_handlers import InboundMailHandler 
from google.appengine.ext.webapp.util import run_wsgi_app
from google.appengine.api import urlfetch
from google.appengine.api import app_identity

class MessageHandlerException(Exception):
    """The general exception object thrown by MessageHandler"""
    def __init__(self, msg):
        self.msg = msg
		
class WebHook(InboundMailHandler):
    def receive(self, mail_message):
        logging.info("Received a message from: " + mail_message.sender)
        body = list(mail_message.bodies(content_type='text/plain'))[0]
        logging.info("Body of message: " + body[1].decode())
        
        # Point to the Main Email trigger URL which will poll for all Firehalls
        # Example replace: http://soft-haus.com/svvfd/riprunner/ with the root of you installation
        url = "http://soft-haus.com/svvfd/riprunner/webhooks/email_trigger_webhook.php"
        form_fields = {
            "sender": mail_message.sender,
            "subject": mail_message.subject,
            "to": mail_message.to,
            "date": mail_message.date,
            "body": body[1].decode()
        }
        form_data = urllib.urlencode(form_fields)
        GAE_APP_ID = app_identity.get_application_id()
        GAE_ACCOUNT_NAME = app_identity.get_service_account_name()
        logging.info("AppID: " + GAE_APP_ID + " SAM: " + GAE_ACCOUNT_NAME)
        result = urlfetch.fetch(url=url,
                        payload=form_data,
                        method=urlfetch.POST,
                        headers={'Content-Type': 'application/x-www-form-urlencoded',
                                 'X-RipRunner-Auth-APPID': GAE_APP_ID,
                                 'X-RipRunner-Auth-ACCOUNTNAME': GAE_ACCOUNT_NAME})
        logging.info(result.status_code)
        logging.info(result.content)
		
application = webapp.WSGIApplication([('/_ah/mail/.+', WebHook)],
                                     debug=True)
			
def main():
    run_wsgi_app(application)
    
if __name__ == '__main__':
    main()
