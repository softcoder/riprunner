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
  //final DataContainer dataContainer;

  //const HomePage({Key key, this.dataContainer}): super(key: key);

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

  // DataContainer getDataContainer() {
  //   return widget.dataContainer;
  // }

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
    const int TAB_COUNT = 4;

    return DefaultTabController(
        length: TAB_COUNT,
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
                Tab(text: 'Live Call'),
                Tab(text: 'Map'),
                Tab(text: 'Messages'),
                Tab(text: 'More...'),
              ],
            ),
          ),
          body: TabBarView(
            children: [
              CalloutDetailsPage(dataContainer: Provider.of<DataContainer>(context)),
              CalloutMapPage(dataContainer: Provider.of<DataContainer>(context)),
              //ActionsPage(dataContainer: Provider.of<DataContainer>(context)),
              ActionsPage(),
              Icon(Icons.more),
            ],
          ),
        )
    );
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