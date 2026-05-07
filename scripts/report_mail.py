import smtplib
import sys
import json
from email.mime.text import MIMEText
from email.header import Header

def send_mail(config, recipients, subject, body):
    try:
        msg = MIMEText(body, 'plain', 'utf-8')
        msg['Subject'] = Header(subject, 'utf-8')
        msg['From'] = config['user']
        msg['To'] = recipients

        server = smtplib.SMTP(config['host'], int(config['port']))
        if config.get('use_tls'):
            server.starttls()
        
        server.login(config['user'], config['password'])
        server.sendmail(config['user'], recipients.split(','), msg.as_string())
        server.quit()
        return True, "Success"
    except Exception as e:
        return False, str(e)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"result": "error", "message": "Missing arguments"}))
        sys.exit(1)
    
    try:
        # Expecting a JSON string as the first argument
        data = json.loads(sys.argv[1])
        
        config = {
            "host": data['smtp_host'],
            "port": data['smtp_port'],
            "user": data['smtp_user'],
            "password": data['smtp_pass'],
            "use_tls": data.get('smtp_tls', True)
        }
        
        recipients = data['recipients']
        subject = data['subject']
        body = data['body']
        
        success, message = send_mail(config, recipients, subject, body)
        if success:
            print(json.dumps({"result": "success"}))
        else:
            print(json.dumps({"result": "error", "message": message}))
            
    except Exception as e:
        print(json.dumps({"result": "error", "message": str(e)}))
