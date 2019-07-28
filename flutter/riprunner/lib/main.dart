import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';

import 'package:background_fetch/background_fetch.dart';
import 'package:flutter/services.dart';

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
  //bool _enabled = true;
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

  // void _onClickEnable(enabled) {
  //   setState(() {
  //     _enabled = enabled;
  //   });
  //   if (enabled) {
  //     BackgroundFetch.start().then((int status) {
  //       print('[BackgroundFetch] start success: $status');
  //     }).catchError((e) {
  //       print('[BackgroundFetch] start FAILURE: $e');
  //     });
  //   } else {
  //     BackgroundFetch.stop().then((int status) {
  //       print('[BackgroundFetch] stop success: $status');
  //     });
  //   }
  // }

  // void _onClickStatus() async {
  //   int status = await BackgroundFetch.status;
  //   print('[BackgroundFetch] status: $status');
  //   setState(() {
  //     _status = status;
  //   });
  // }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Rip Runner',
      theme: ThemeData(
        primarySwatch: Colors.blue,
      ),
      home: MyHomePage(title: 'Rip Runner Home Page'),
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
  //var _androidAppRetain = MethodChannel("android_app_retain");
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
