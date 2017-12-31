import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { MenuCalloutHistoryComponent } from './menu-callout-history.component';

describe('MenuCalloutHistoryComponent', () => {
  let component: MenuCalloutHistoryComponent;
  let fixture: ComponentFixture<MenuCalloutHistoryComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ MenuCalloutHistoryComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(MenuCalloutHistoryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
