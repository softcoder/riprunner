import { Component, ElementRef, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';

import { AuthService } from '@app/auth';
import { SystemConfigService } from '@app/common';

@Component({
  selector: 'app-menu-callout-monitor',
  templateUrl: './menu-callout-monitor.component.html',
  styleUrls: ['./menu-callout-monitor.component.css']
})

export class MenuCalloutMonitorComponent implements OnInit {

  @ViewChild('connection',{static:false}) public connection: ElementRef;
  @ViewChild('dialogMessage',{static:false}) public dialogMessage: ElementRef;

  lastCalloutURL: string;
  source;
  logger: Logger;

  private ast: string;
  private fhid: string;
  private member_id: string;

  private sub: any;
  public systemConfig;

  constructor(private location: Location,
              private route: ActivatedRoute,
              private authService: AuthService,
              private systemConfigService: SystemConfigService) {
  }

  ngOnInit() {
    debugger;

    let authSpecialParams = '';
    this.sub = this.route
      .queryParams
      .subscribe(params => {
        this.ast = params['ast'] || '';
        this.fhid = params['fhid'] || '';
        this.member_id = params['member_id'] || '';

        if (this.ast !== '' && this.fhid !== '' && this.member_id !== '') {
          authSpecialParams = `&ast=${this.ast}&fhid=${this.fhid}&member_id=${this.member_id}`;
        }
    });

    this.logger = new Logger('log');
    const thisInstance = this;

    const win = window.open('_blank');
    if (win == null) {
        debugger;
        console.info('In window.open ERROR!!! cannot popup page likely blocked from popup blocker!.');
        alert('CANNOT Open popups as you have a popup blocker! Please allow popus for this website and reload the page.');
    }
    else {
      setTimeout(function() { win.close(); }, 500);
    }

    // sends messages with text/event-stream mimetype.
    const url = this.getExternalUrl(`controllers/callout-monitor-controller.php?server_mode=true${authSpecialParams}`);
    this.source = new EventSource(url);
    this.source.addEventListener('message', function(event) {
      try {
        //debugger;

        const data = JSON.parse(event.data);
        if (data.id > 0) {
          debugger;
          thisInstance.closeConnection();
          thisInstance.logger.log('Got a live page!', 'msg');

          const config = thisInstance.systemConfigService.getSystemConfig();
          const astParams = (thisInstance.ast !== '' ? `&member_id=${thisInstance.member_id}` : '');
          const details_url = `ngui/index.html?page=call-details&fhid=${config.firehall_id}&cid=${data.id}&ckid=${data.keyid}${astParams}`;
          const callout_url = thisInstance.getExternalUrl(details_url + authSpecialParams);

          thisInstance.signalPage(callout_url);
          thisInstance.logger.log('Playing page tone!', 'msg');
        }
        thisInstance.logger.log('lastEventID: ' + event.lastEventId + ', server msg: ' + data.keyid + ' id = ' + data.id, 'msg');
      }
      catch(ex) {
        debugger;
        console.info('ERROR In addEventListener(message) error: ' + ex.message);
      }
    }, false);

    this.source.addEventListener('open', function(event) {
      //debugger;
      console.info('Connection was opened at: ' + new Date().toLocaleString());
      thisInstance.logger.clear();
      thisInstance.logger.log('> Connection was opened at: ' + new Date().toLocaleString());
      thisInstance.updateConnectionStatus('Connected', true);
    }, false);

    this.source.addEventListener('error', function(event) {
      debugger;
      if (event.eventPhase == 2) { // EventSource.CLOSED
        console.info('Connection was closed at: ' + new Date().toLocaleString());
        thisInstance.logger.log('> Connection was closed (2) at: ' + new Date().toLocaleString());
        thisInstance.updateConnectionStatus('Disconnected', false);
      }
      else {
        console.info('Connection error: ' + event.eventPhase + 'at: ' + new Date().toLocaleString());
      }
    }, false);

    console.info('Will try to connect to url: ' + this.source.url);
  }

  ngOnDestroy() {
    debugger;
    if (this.sub !== undefined) {
      this.sub.unsubscribe();
    }
  }

  signalPage(calloutUrl: string) {
    debugger;

    try {
      console.info('In signalPage start for url: ' + calloutUrl);
      if (this.lastCalloutURL == null && this.lastCalloutURL !== calloutUrl) {
        const thisInstance = this;
        const audio = new Audio();
        audio.src = './assets/sounds/pager_tone_pg.mp3';
        audio.load();
        audio.addEventListener('ended', function() {
          debugger;
          console.info('In signalPage playing tones ENDED shows call details for url: ' + calloutUrl);
          const win = window.open(calloutUrl);
          if (win == null) {
              debugger;
              console.info('In window.open ERROR!!! cannot popup page likely blocked from popup blocker!.');

              alert('Page detected, however we CANNOT Open popups as you have a popup blocker!');
          }
          console.info('In signalPage playing tones ENDED complete');
        }, false);
        debugger;
        audio.play();

        console.info('In signalPage playing tones: sounds/pager_tone_pg.mp3');
      }
      else {
        console.info('In signalPage callout already signalled, doing nothing.');
      }
    }
    catch(ex) {
      debugger;
      console.info('ERROR In signalPage start for url: ' + calloutUrl + ' error: ' + ex.message);
      alert('Pager tone error: ' + ex.message);
      window.location.href = calloutUrl;
    }
  }

  closeConnection() {
    debugger;
    this.source.close();
    this.logger.log('> Connection was closed at: ' + new Date().toLocaleString());
    console.info('> Connection was closed at: ' + new Date().toLocaleString());
    this.updateConnectionStatus('Disconnected', false);
  }

  updateConnectionStatus(msg, connected) {
    //debugger;
    try {
      if (connected) {
        console.info('Connection connected at: ' + new Date().toLocaleString());
        if (this.connection.nativeElement.classList) {
          this.connection.nativeElement.classList.add('connected');
          this.connection.nativeElement.classList.remove('disconnected');
        }
        else {
          this.connection.nativeElement.addClass('connected');
          this.connection.nativeElement.removeClass('disconnected');
        }
      }
      else {
        console.info('Connection disconnected at: ' + new Date().toLocaleString());
        if (this.connection.nativeElement.classList) {
          this.connection.nativeElement.classList.remove('connected');
          this.connection.nativeElement.classList.add('disconnected');
        }
        else {
          this.connection.nativeElement.removeClass('connected');
          this.connection.nativeElement.addClass('disconnected');
        }
      }
      this.connection.nativeElement.innerHTML = msg + '<div></div>';
    }
    catch(ex) {
      debugger;
      console.info('ERROR In updateConnectionStatus error: ' + ex.message);
    }
  }

  private getRootPath(): string {
    return this.location.prepareExternalUrl('../');
  }

  private getExternalUrl(url: string, handOffJWT: boolean = true): string {
    // debugger;
    url = this.getRootPath() + url;
    return this.authService.injectJWTtoken(url, handOffJWT);
  }
}

class Logger {
  el: HTMLElement;

  constructor(id) {
    // debugger;
    this.el = document.getElementById(id);
  }

  log(msg, opt_class = null) {
    // debugger;
    const fragment = document.createDocumentFragment();
    const p = document.createElement('p');
    p.className = opt_class || 'info';
    p.textContent = msg;
    fragment.appendChild(p);
    this.el.appendChild(fragment);
  }

  clear() {
    // debugger;
    this.el.textContent = '';
  }
}

interface Callback { (data: any): void; }

declare class EventSource {
    onmessage: Callback;
    addEventListener(event: string, cb: Callback): void;
    constructor(name: string);
}
