import 'dart:async';
import 'package:audioplayer/audioplayer.dart';
import 'package:flutter/material.dart';
import 'package:modal_progress_hud/modal_progress_hud.dart';

import 'app_constants.dart';
import 'auth/auth.dart';
import 'common/sounds.dart';
import 'common/utils.dart';
import 'home_page.dart';
import 'app_settings.dart';
import 'common/choice.dart';

class LoginPage extends StatefulWidget {
  static String tag = 'login-page';

  @override
  _LoginState createState() => new _LoginState();
}

class _LoginState extends State<LoginPage> {
  String websiteUrlStr;
  String firehallId;
  String userId;
  bool launchSettings = false;
  bool _inProgress = false;
  String loginStatus = 'Waiting for credentials...';

  TextEditingController textCtlFHID = new TextEditingController();
  TextEditingController textCtlUser = new TextEditingController();
  TextEditingController textCtlPwd = new TextEditingController();

  final List<Choice> choices = const <Choice>[
    const Choice(title: 'Settings', icon: Icons.settings_applications),
  ];

  AudioPlayer audioPlayer = new AudioPlayer();

  void dispose() {
    textCtlFHID.dispose();
    textCtlUser.dispose();
    textCtlPwd.dispose();
    super.dispose();
  }

  Future<void> loadState() async {
      launchSettings = await Utils.hasConfigItem<String>(AppConstants.PROPERTY_WEBSITE_URL) == false;
      if(launchSettings == false) {
        websiteUrlStr = await Utils.getConfigItem<String>(AppConstants.PROPERTY_WEBSITE_URL);
        firehallId = await Utils.getConfigItem<String>(AppConstants.PROPERTY_FIREHALL_ID);
        userId = await Utils.getConfigItem<String>(AppConstants.PROPERTY_USER_ID);
      }
      else {
        Navigator.of(context).pushNamed(AppSettingsPage.tag);        
        return;
      }

      setState(() {
        launchSettings = launchSettings;
        websiteUrlStr = websiteUrlStr;
        firehallId = firehallId;
        userId = userId;
      });
  }

  @override
  void initState() {
    super.initState();
    Authentication.logout();
    loadState();
  }  

  Future<void> processLoginResult(AuthResponse auth) async {
    if(auth.status == true) {
      setState(() {
        loginStatus = 'Welcome: ' + auth.user;

        Utils.setConfigItem<String>(AppConstants.PROPERTY_FIREHALL_ID,firehallId);
        Utils.setConfigItem<String>(AppConstants.PROPERTY_USER_ID,userId);
        Utils.setConfigItem<String>(AppConstants.PROPERTY_AUTH,auth.token);
        Navigator.of(context).popAndPushNamed(HomePage.tag);
      });
    }
    else {
      setState(() {
        loginStatus = 'Login Error: ' + auth.message;  
      });
    }
  }

  void login() {
    setState(() {
      _inProgress = true;
    });
    String pwd = textCtlPwd.text;
    textCtlPwd.text = '';
    Authentication.login(textCtlFHID.text, textCtlUser.text, pwd).
      then((auth) => processLoginResult(auth)).
      catchError((e) {
        setState(() {
          loginStatus = 'Login Error: ' + e.toString();
        });
      }).
      whenComplete(() {
        setState(() {
          SoundUtils.playSound(audioPlayer, 'assets/sounds/login.mp3',ResourceType.LocalAsset);
          _inProgress = false;
        });
      });
  }

  @override
  Widget build(BuildContext context) {
    List<Widget> widgets;

    final logo = Hero(
      tag: 'hero',
      child: CircleAvatar(
        backgroundColor: Colors.transparent,
        radius: 100.0,
        child: Image.asset('assets/generic_logo3.png'),
        ),
    );

    final firehallLabel = FlatButton(
      child: Text(
        'Firehall Id:',
        style: TextStyle(color: Colors.black54)),
        onPressed: () { },
    );

    textCtlFHID.text = firehallId ?? '';
    final firehall = TextField(
      autofocus: false,
      controller: textCtlFHID,
      onChanged: (text) {
        firehallId = text;
      },
      decoration: InputDecoration(
        hintText: 'Firehall Id',
        contentPadding: EdgeInsets.fromLTRB(20.0, 10.0, 20.0, 10.0),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32.0)
        ),
      ),
    );

    final userLabel = FlatButton(
      child: Text(
        'Username:',
        style: TextStyle(color: Colors.black54)),
        onPressed: () { },
    );

    textCtlUser.text = userId ?? '';
    final email = TextField(
      keyboardType: TextInputType.emailAddress,
      autofocus: false,
      controller: textCtlUser,
      onChanged: (text) {
        userId = text;
      },
      decoration: InputDecoration(
        hintText: 'Username',
        contentPadding: EdgeInsets.fromLTRB(20.0, 10.0, 20.0, 10.0),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32.0)
        ),
      ),
    );

    final passwordLabel = FlatButton(
      child: Text(
        'Password:',
        style: TextStyle(color: Colors.black54)),
        onPressed: () { },
    );

    final password = TextField(
      autofocus: false,
      controller: textCtlPwd,
      obscureText: true,
      decoration: InputDecoration(
        hintText: 'Password',
        contentPadding: EdgeInsets.fromLTRB(20.0, 10.0, 20.0, 10.0),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32.0)
        ),
      ),
    );

    final loginButton = Padding(
      padding: EdgeInsets.symmetric(vertical: 16.0),
      child: Material(
        borderRadius: BorderRadius.circular(30.0),
        shadowColor: Colors.lightBlueAccent.shade100,
        elevation: 5.0,
        child: MaterialButton(
          minWidth: 200.0,
          height: 42.0,
          onPressed: () {
            login();
          },
          color: Colors.lightBlueAccent,
          child: Text('Login',style: TextStyle(color: Colors.white)),
        ),
      ),
    );

   final statusLabel = FlatButton(
      child: Text(
        loginStatus,
        style: TextStyle(color: Colors.black54)),
        onPressed: () { },
    );

    widgets = <Widget>[
          logo,
          SizedBox(height: 4.0),
          firehallLabel,
          firehall,
          SizedBox(height: 4.0),
          userLabel,
          email,
          SizedBox(height: 4.0),
          passwordLabel,
          password,
          SizedBox(height: 4.0),
          loginButton,
          statusLabel
          ];
  
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
          title: const Text('Rip Runner'),
          actions: <Widget>[
            PopupMenuButton<Choice>(
              onSelected: _select,
              itemBuilder: (BuildContext context) {
                return choices.map((Choice choice) {
                  return PopupMenuItem<Choice>(
                    value: choice,
                    child: Text(choice.title),
                  );
                }).toList();
              },
            ),
          ],
      ),
      body: ModalProgressHUD(
        child: Center(
          child: ListView(
            shrinkWrap: true,
            padding: EdgeInsets.only(left: 24.0, right: 24.0),
            children: widgets,
          ),
        ),
        inAsyncCall: _inProgress
      )
    );
  }

  void _select(Choice choice) {
      if(choice.title == "Settings") {
        Navigator.of(context).pushNamed(AppSettingsPage.tag);
      }
  }  
}