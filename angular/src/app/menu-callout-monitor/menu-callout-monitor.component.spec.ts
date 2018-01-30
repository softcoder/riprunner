import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { MenuCalloutMonitorComponent } from './menu-callout-monitor.component';

describe('MenuCalloutMonitorComponent', () => {
  let component: MenuCalloutMonitorComponent;
  let fixture: ComponentFixture<MenuCalloutMonitorComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ MenuCalloutMonitorComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(MenuCalloutMonitorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
