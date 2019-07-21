import 'package:flutter/material.dart';

import 'login_page.dart';
import 'home_page.dart';
import 'app_settings.dart';
import 'callout_details.dart';

void main() => runApp(MyApp());

class MyApp extends StatelessWidget {

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

  final routes = <String, WidgetBuilder> {
    MyHomePage.tag:         (context)=>MyHomePage(),
    LoginPage.tag:          (context)=>LoginPage(),
    HomePage.tag:           (context)=>HomePage(),
    AppSettingsPage.tag:    (context)=>AppSettingsPage(),
    CalloutDetailsPage.tag: (context)=>CalloutDetailsPage(),
  };
  
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
  }
}
