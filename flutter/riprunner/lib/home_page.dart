import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'actions_page.dart';
import 'auth/auth.dart';
import 'common/data_container.dart';
import 'common/utils.dart';
import 'app_constants.dart';
import 'app_settings.dart';
import 'common/choice.dart';
import 'login_page.dart';
import 'callout_details.dart';
import 'callout_map.dart';

class HomePage extends StatefulWidget {
  static String tag = 'home-page';

  @override
  _HomePageState createState() => new _HomePageState();
}

const String LOGOUT_ACTION = "Logout";
const String SETTINGS_ACTION = "Settings";

class _HomePageState extends State<HomePage> with SingleTickerProviderStateMixin {

  TabController tabController;
  String firehallId;
  String userId;

  final List<Choice> choices = const <Choice>[
    const Choice(title: SETTINGS_ACTION, icon: Icons.settings_applications),
    const Choice(title: LOGOUT_ACTION, icon: Icons.exit_to_app),
  ];

  DataContainer getDataContainer({bool listenValue=true}) {
    return Provider.of<DataContainer>(context, listen: listenValue);
  }

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

    const int TAB_COUNT = 4;
    tabController = new TabController(
        vsync: this,
        length: TAB_COUNT,
    );

  }  
  @override
  void dispose() {
    super.dispose();
    if(tabController != null) {
      tabController.dispose();
    }
  }

  @override
  Widget build(BuildContext context) {
    
    Scaffold widget = new Scaffold(
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
            controller: tabController,
            tabs: [
              Tab(text: 'Live Call'),
              Tab(text: 'Map'),
              Tab(text: 'Messages'),
              Tab(text: 'More...'),
            ],
          ),
        ),
        body: TabBarView(
          controller: tabController,
          children: [
            CalloutDetailsPage(),
            CalloutMapPage(),
            ActionsPage(),
            Icon(Icons.more),
          ],
        ),
      );

    if(getDataContainer().getDataFromMap('CHAT_MESSAGES').isNotEmpty) {
      const int TAB_ACTIONS_INDEX = 2;
      tabController.animateTo(TAB_ACTIONS_INDEX);
    }
    return widget;
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