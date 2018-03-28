import { TestBed, inject } from '@angular/core/testing';

import { SendMessageService } from './send-message.service';

describe('SendMessageService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [SendMessageService]
    });
  });

  it('should be created', inject([SendMessageService], (service: SendMessageService) => {
    expect(service).toBeTruthy();
  }));
});
