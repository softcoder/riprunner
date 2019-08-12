import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';

import 'package:background_fetch/background_fetch.dart';
import 'package:flutter/services.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:provider/provider.dart';

import 'app_constants.dart';
import 'common/data_container.dart';
import 'common/utils.dart';
import 'login_page.dart';
import 'home_page.dart';
import 'app_settings.dart';


void main() {
  // Enable integration testing with the Flutter Driver extension.
  // See https://flutter.io/testing/ for more info.
  runApp(new MyApp());

  // Register to receive BackgroundFetch events after app is terminated.
  // Requires {stopOnTerminate: false, enableHeadless: true}
  BackgroundFetch.registerHeadlessTask(backgroundFetchHeadlessTask);
}

/// This "Headless Task" is run when app is terminated.
void backgroundFetchHeadlessTask() async {
  print('[BackgroundFetch] Headless event received.');
  BackgroundFetch.finish();
}

class MyApp extends StatefulWidget {
  @override
  _MyAppState createState() => new _MyAppState();
}

class _MyAppState extends State<MyApp> {
  int _status = 0;
  List<DateTime> _events = [];

  @override
  void initState() {
    super.initState();

    initPlatformState();
  }

  // Platform messages are asynchronous, so we initialize in an async method.
  Future<void> initPlatformState() async {
    // Configure BackgroundFetch.
    BackgroundFetch.configure(BackgroundFetchConfig(
        minimumFetchInterval: 15,
        stopOnTerminate: false,
        enableHeadless: true
    ), () async {
      // This is the fetch-event callback.
      print('[BackgroundFetch] Event received');
      setState(() {
        _events.insert(0, new DateTime.now());
      });
      // IMPORTANT:  You must signal completion of your fetch task or the OS can punish your app
      // for taking too long in the background.
      BackgroundFetch.finish();
    }).then((int status) {
      print('[BackgroundFetch] SUCCESS: $status');
      setState(() {
        _status = status;
      });
    }).catchError((e) {
      print('[BackgroundFetch] ERROR: $e');
      setState(() {
        _status = e;
      });
    });

    // Optionally query the current BackgroundFetch status.
    int status = await BackgroundFetch.status;
    setState(() {
      _status = status;
    });

    // If the widget was removed from the tree while the asynchronous platform
    // message was in flight, we want to discard the reply rather than calling
    // setState to update our non-existent appearance.
    if (!mounted) return;
  }

  @override
  Widget build(BuildContext context) {

    return MaterialApp(
      title: 'Rip Runner',
      theme: ThemeData(
        primarySwatch: Colors.blue,
      ),
      home: Provider<DataContainer>(
        builder: (context) => DataContainer(data: {}, dataMap: { 'CHAT_MESSAGES': [] }),
        //dispose: (context, value) => value.dispose(),
        child: MyHomePage(title: 'Rip Runner Home Page'),
      )
    );
  }
}

class MyHomePage extends StatefulWidget {

  static String tag = 'main-page';
  final String title;

  MyHomePage({Key key, this.title}) : super(key: key);

  @override
  _MyHomePageState createState() => _MyHomePageState();
}

class _MyHomePageState extends State<MyHomePage> {

  FirebaseMessaging firebaseMessaging = FirebaseMessaging();
  String _message = '';

  var _androidAppRetain = MethodChannel("riprinner_android_app_retain");
  
  final routes = <String, WidgetBuilder> {
    MyHomePage.tag:         (context)=>MyHomePage(),
    LoginPage.tag:          (context)=>LoginPage(),
    HomePage.tag:           (context)=>HomePage(),
    AppSettingsPage.tag:    (context)=>AppSettingsPage(),
  };
  
  @override
  void initState() {
    super.initState();
    
    firebaseCloudMessagingListeners();
    _androidAppRetain.setMethodCallHandler((call) {
      print("In _androidAppRetain.setMethodCallHandler: $call.method");
    });

    if (Platform.isAndroid) {
      _androidAppRetain.invokeMethod("wasActivityKilled").then((result){
        if (result) {
          showDialog(
              context: context,
              builder: (context) {
                return activityGotKilledDialog();
              });
        }
      });
    }
  }

  void registerDevice() async {
    firebaseMessaging.getToken().then((token) { 
      print(token);

      //Log.i(Utils.TAG, "GCM Registration Token: " + token);
      Utils.setConfigItem<String>(AppConstants.PROPERTY_REG_ID, token);
      Utils.setConfigItem<bool>(AppConstants.GOT_TOKEN_FROM_SERVER, true);
    });
  }

  void setupFCMRegistration(bool forceReg) async {
      if (forceReg || 
              ((await Utils.hasConfigItem<bool>(AppConstants.GOT_TOKEN_FROM_SERVER) == false) ||
               (await Utils.hasConfigItem<String>(AppConstants.PROPERTY_REG_ID) == false) ||
               (await Utils.getConfigItem<String>(AppConstants.PROPERTY_REG_ID)).isEmpty)) {
          if ((await Utils.hasConfigItem<String>(AppConstants.PROPERTY_SENDER_ID)) &&
                  (await Utils.getConfigItem<String>(AppConstants.PROPERTY_SENDER_ID)).isEmpty == false) {
              registerDevice();
          }
      }
  }

  void iosPermission() {
    if (Platform.isIOS) {
      firebaseMessaging.requestNotificationPermissions(
          IosNotificationSettings(sound: true, badge: true, alert: true)
      );
      firebaseMessaging.onIosSettingsRegistered
          .listen((IosNotificationSettings settings)
      {
        print("Settings registered: $settings");
      });
    }
  }

  void firebaseCloudMessagingListeners() {
    iosPermission();
    setupFCMRegistration(false);

    firebaseMessaging.configure(
      onMessage: (Map<String, dynamic> message) async {
        print('on message $message');
        processFCMMessageEvent(message);
    }, 
      onResume: (Map<String, dynamic> message) async {
        print('on resume $message');
        setState(() => _message = message["notification"]["title"]);
    }, 
      onLaunch: (Map<String, dynamic> message) async {
        print('on launch $message');
        setState(() => _message = message["notification"]["title"]);
    });
  }

  void processFCMMessageEvent(Map<String, dynamic> message) {
    try {
      var messageMap = Map<String, dynamic>.from(message['data']);
      processFCMMessage(messageMap);
      setState(() => _message = message["notification"]["title"]);
    }
    catch(e) {
      print("In processFCMMessageEvent" + e.toString());
    }
  }

  void processFCMMessage(Map<String, dynamic> messageMap) {
    print("Start processFCMMessage: " + messageMap.toString());

    if(messageMap.containsKey("DEVICE_MSG")) {
        Utils.processDeviceMsgTrigger(messageMap);
    } 
    else if(messageMap.containsKey("CALLOUT_MSG")) {
        Utils.processCalloutTrigger(messageMap);
    } 
    else if(messageMap.containsKey("CALLOUT_RESPONSE_MSG")) {
        Utils.processCalloutResponseTrigger(messageMap);
    } 
    else if(messageMap.containsKey("ADMIN_MSG")) {
        DataContainer data = Provider.of<DataContainer>(context);
        Utils.processAdminMsgTrigger(messageMap, data);
    } 
    else {
        print(": Broadcaster got UNKNOWN callout message type: " + messageMap.toString());
    }    
  }

  Widget activityGotKilledDialog() {
    return AlertDialog(
            title: Text('Material Dialog - activityGotKilledDialog'),
            content: Text('This is the content of the material dialog - activityGotKilledDialog'),
            actions: <Widget>[
              FlatButton(
                  onPressed: () {
                  _dismissDialog();
                  },
                  child: Text('Close')),
              FlatButton(
                onPressed: () {
                  print('HelloWorld! - activityGotKilledDialog');
                  _dismissDialog();
                },
                child: Text('Print HelloWorld! - activityGotKilledDialog'),
              )
            ],
          );
  }

  void _dismissDialog() {
    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Rip Runner',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.lightBlue,
        fontFamily: 'Nunito',
      ),
      home: LoginPage(),
      routes: routes,
    );

    // return WillPopScope(
    //   onWillPop: () {
    //     if (Platform.isAndroid) {
    //       if (Navigator.of(context).canPop()) {
    //         return Future.value(true);
    //       } else {
    //         _androidAppRetain.invokeMethod("sendToBackground");
    //         return Future.value(false);
    //       }
    //     } else {
    //       return Future.value(true);
    //     }
    //   },
    //   child: LoginPage()
    //   );
  }
}
