import 'package:flutter/material.dart';
import 'package:riprunner/callout_details.dart';

import 'auth/auth.dart';
import 'common/utils.dart';
import 'app_constants.dart';
import 'app_settings.dart';
import 'common/choice.dart';
import 'login_page.dart';

class HomePage extends StatefulWidget {
  static String tag = 'home-page';
  @override
  _HomePageState createState() => new _HomePageState();
}

const String LOGOUT_ACTION = "Logout";
const String SETTINGS_ACTION = "Settings";

class _HomePageState extends State<HomePage> {
  String firehallId;
  String userId;

  final List<Choice> choices = const <Choice>[
    const Choice(title: SETTINGS_ACTION, icon: Icons.settings_applications),
    const Choice(title: LOGOUT_ACTION, icon: Icons.exit_to_app),
  ];

  Future<void> loadState() async {
    bool launchSettings = await Utils.hasConfigItem<String>(AppConstants.PROPERTY_WEBSITE_URL) == false;
    if(launchSettings == false) {
      firehallId = await Utils.getConfigItem<String>(AppConstants.PROPERTY_FIREHALL_ID);
      userId = await Utils.getConfigItem<String>(AppConstants.PROPERTY_USER_ID);
      setState(() {
        firehallId = firehallId;
        userId = userId;
      });
    }
  }

  @override
  void initState() {
    super.initState();
    loadState();
  }  

  @override
  Widget build(BuildContext context) {

    final tabs = DefaultTabController(
        length: 3,
        child: Scaffold(
          appBar: AppBar(
            title: const Text('Rip Runner'),
            actions: <Widget>[
              IconButton(
                icon: Icon(choices[1].icon),
                onPressed: () {
                  _select(choices[1]);
                },
              ),
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
            bottom: TabBar(
              tabs: [
                Tab(text: 'Current Activity'),
                Tab(text: 'Map'),
                Tab(text: 'More Actions'),
                //Tab(icon: Icon(Icons.directions_transit)),
                //Tab(icon: Icon(Icons.directions_bike)),
              ],
            ),
          ),
          body: TabBarView(
            children: [
              CalloutDetailsPage(),
              Icon(Icons.map),
              Icon(Icons.more),
            ],
          ),
        )
    );

    return tabs;
  }
  
  void _select(Choice choice) {
      if(choice.title == SETTINGS_ACTION) {
        Navigator.of(context).pushNamed(AppSettingsPage.tag);
      }
     if(choice.title == LOGOUT_ACTION) {
        Authentication.logout();
        Navigator.of(context).popAndPushNamed(LoginPage.tag);
     }      
  }  
}