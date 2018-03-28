import { Component, Input, OnInit } from '@angular/core';
import { MessageContext, SendMessageService } from '../../send-message.service';

@Component({
  selector: 'app-send-message',
  templateUrl: './send-message.component.html',
  styleUrls: ['./send-message.component.css']
})
export class SendMessageComponent implements OnInit {

  textValue = '';
  msgStatus = null;
  @Input() users: string;

  constructor(private msgService: SendMessageService) { }

  ngOnInit() {
  }

  toggleVisibility(itemId) {
    const item = document.getElementById(itemId);
    if (item.style.display === 'none') {
        item.style.display = 'block';
    }
    else {
        item.style.display = 'none';
    }
  }

  send_msg(msg_type) {
    // debugger;
    if (this.textValue == null || this.textValue.length === 0) {
      this.msgStatus = 'Need to type a message!';
      return;
    }
    if (this.users == null || this.users.length === 0) {
      this.msgStatus = 'Need to select one or more users!';
      return;
    }
    const msgContext = new MessageContext();
    msgContext.type = msg_type;
    msgContext.msg = this.textValue;
    msgContext.users = this.users;
    return this.msgService.send(msgContext).then(response => {
      //debugger;
      this.msgStatus = response.status;
    });
  }
}
