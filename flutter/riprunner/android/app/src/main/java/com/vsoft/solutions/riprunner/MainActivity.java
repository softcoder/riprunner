package com.vsoft.solutions.riprunner;

import android.app.AlarmManager;
import android.app.PendingIntent;
import android.content.Intent;
import java.lang.Object;
import android.os.Bundle;
import android.util.Log;
import io.flutter.app.FlutterActivity;
import io.flutter.plugin.common.MethodCall;
import io.flutter.plugin.common.MethodChannel;
import io.flutter.plugins.GeneratedPluginRegistrant;
import io.flutter.view.FlutterView;

public class MainActivity extends FlutterActivity implements MethodChannel.MethodCallHandler {

  private PendingIntent pendingIntent;
  private AlarmManager alarmManager;
  private static  FlutterView flutterView;
  private static final String CHANNEL = "riprinner_android_app_retain";
  private static final String TAG = "MainActivityRipRunner";

  @Override
  protected void onCreate(Bundle savedInstanceState) {

      super.onCreate(savedInstanceState);
      Log.w(TAG, "In onCreate - A savedInstanceState = " + (savedInstanceState != null ? "not null" : "null"));

      flutterView=getFlutterView();
      GeneratedPluginRegistrant.registerWith(this);

      Log.w(TAG, "In onCreate - B flutterView = " + (flutterView != null ? "not null" : "null"));

      new MethodChannel(flutterView, CHANNEL).setMethodCallHandler(this::onMethodCall);

      if (savedInstanceState != null && getLastNonConfigurationInstance() == null) {
        // Finish Activity
        Log.w(TAG, "In onCreate - C");
        MethodChannel methodChannel=new MethodChannel(flutterView, CHANNEL);
        methodChannel.invokeMethod("Detected application restart!","");
        Log.w(TAG, "In onCreate - D");
      }
      Log.w(TAG, "In onCreate - E");
      Intent intent = new Intent(this, MyReceiver.class);
      pendingIntent = PendingIntent.getBroadcast(this, 1019662, intent, 0);
      alarmManager = (AlarmManager) getSystemService(ALARM_SERVICE);
      alarmManager.setInexactRepeating(AlarmManager.RTC_WAKEUP, System.currentTimeMillis(), 60000 * 15, pendingIntent);
      Log.w(TAG, "In onCreate - F");
  }

  @Override
  protected void onDestroy() {
      super.onDestroy();
      Log.w(TAG, "In onDestroy - A");
      alarmManager.cancel(pendingIntent);
  }

  //@Override
  public Object onRetainCustomNonConfigurationInstance() {
    Log.w(TAG, "In onRetainCustomNonConfigurationInstance - A");
    return new Object();
  }

  static void callFlutter() {
    Log.w(TAG, "In callFlutter - A");
    MethodChannel methodChannel=new MethodChannel(flutterView, CHANNEL);
    methodChannel.invokeMethod("I say hello every 15 minutes!","");
  }
  public void wasActivityKilled() {
      Log.w(TAG, "In wasActivityKilled - A");
      int ii = 0;
  }

  @Override
  public void onMethodCall(MethodCall call, MethodChannel.Result result) {
    try {
        Log.w(TAG, "In onMethodCall - A - call.method = " + (call != null && call.method != null ? call.method : "null"));

        if(call.method.equals("wasActivityKilled")) {
            int ii = 0;
        }
        // if (call.method.equals("connect")) {
        //     connectToService();
        //     keepResult = result;
        // } else if (serviceConnected) {
        //     if (call.method.equals("start")) {
        //         appService.startTimer(call.argument("duration"));
        //         result.success(null);
        //     } else if (call.method.equals("stop")) {
        //         appService.stopTimer();
        //         result.success(null);
        //     } else if (call.method.equals("getCurrentSeconds")) {
        //         int sec = appService.getCurrentSeconds();
        //         result.success(sec);
        //     }
        //} 
        else {
            result.error(null, "App not connected to service", null);
        }
    } catch (Exception e) {
        result.error(null, e.getMessage(), null);
    }
  }  
}
