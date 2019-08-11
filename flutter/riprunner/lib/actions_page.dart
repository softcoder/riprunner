import 'dart:async';

import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import 'common/data_container.dart';
import 'common/chat_message.dart';

class ActionsPage extends StatefulWidget {
  static String tag = 'actions-page';

  @override
  _ActionsPageState createState() => new _ActionsPageState();
}

class _ActionsPageState extends State<ActionsPage> with AutomaticKeepAliveClientMixin<ActionsPage> {
  
  @override
  bool get wantKeepAlive => true;

  final themeColor = Color(0xfff5a623);
  final primaryColor = Color(0xff203152);
  final greyColor = Color(0xffaeaeae);
  final greyColor2 = Color(0xffE8E8E8);

  final ScrollController listScrollController = new ScrollController();
  String userId;
  String groupChatId;

  DataContainer getDataContainer({bool listenValue=true}) {
    return Provider.of<DataContainer>(context, listen: listenValue);
  }

  void dispose() {
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    
    groupChatId = '';
    setupInitialState();
  }

  void setupInitialState() {
    // Testing
    print("In actions_page setupInitialState");
    // getDataContainer(listenValue: false).getDataFromMap('CHAT_MESSAGES').add(
    //   ChatMessage('Admin', '', 'This is a test message.', ChatMessageType.admin, DateTime.now()));
    // getDataContainer(listenValue: false).getDataFromMap('CHAT_MESSAGES').add(
    //   ChatMessage('mark.vejvoda', '', 'Test message MV', ChatMessageType.peer, DateTime.now()));
    // getDataContainer(listenValue: false).getDataFromMap('CHAT_MESSAGES').add(
    //   ChatMessage('mark.vejvoda', '', 'Test message MV2', ChatMessageType.peer, DateTime.now()));
    // getDataContainer(listenValue: false).getDataFromMap('CHAT_MESSAGES').add(
    //   ChatMessage('corey.davoren', '', 'Hey everyone Practice is cancelled tomorrow night, enjoy the weekend :)', ChatMessageType.peer, DateTime.now()));
    //
    userId = getDataContainer(listenValue: false).getDataFromMap('USER_ID');
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    if(getDataContainer().getDataFromMap('CHAT_MESSAGES').isNotEmpty) {
      return new Scaffold(
        body: WillPopScope(
          child: Stack(
            children: <Widget>[
              Column(
                children: <Widget>[
                  // List of messages
                  buildListMessage(getDataContainer().getDataFromMap('CHAT_MESSAGES')),

                  // Sticker
                  //(isShowSticker ? buildSticker() : Container()),
                  Container(),

                  // Input content
                  //buildInput(),
                ],
              ),

              // Loading
              buildLoading()
            ],
          ),
          onWillPop: onBackPress,
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: () {
            setState(() {
              getDataContainer(listenValue: false).getDataFromMap('CHAT_MESSAGES').clear();
              //userId = userId;
            });
          },
          label: Text('Clear'),
          icon: Icon(Icons.clear),
        ),
      );
    }
    else {
      return new Scaffold(
        body: new Container(
        padding: EdgeInsets.all(2.0),
        decoration: BoxDecoration(
            gradient: LinearGradient(colors: [ Colors.black, Colors.black ]),
        ),
        child: SizedBox.expand(
          child: Padding(
            padding: EdgeInsets.all(0.0),
            child: FittedBox(
              fit: BoxFit.contain,
              child: Text(
                'Currently there are no messages...',
                style: TextStyle(fontSize: 20.0, color: Colors.lightGreenAccent),
              ),
            ),
          ),
        ),
      ));
    }
  }

  Widget buildLoading() {
    return Positioned(
      child: 
      // isLoading
      //     ? Container(
      //         child: Center(
      //           child: CircularProgressIndicator(valueColor: AlwaysStoppedAnimation<Color>(themeColor)),
      //         ),
      //         color: Colors.white.withOpacity(0.8),
      //       )
      //     : 
      Container(),
    );
  }

  Future<bool> onBackPress() {
    // if (isShowSticker) {
    //   setState(() {
    //     isShowSticker = false;
    //   });
    // } else {
      //Firestore.instance.collection('users').document(id).updateData({'chattingWith': null});
      Navigator.pop(context);
    //}

    return Future.value(false);
  }

  bool isLastMessageLeft(int index) {
    if ((index > 0 && getDataContainer().getDataFromMap('CHAT_MESSAGES') != null && 
         getDataContainer().getDataFromMap('CHAT_MESSAGES')[index - 1].peerId == userId) || index == 0) {
      return true;
    } 
    else {
      return false;
    }
  }

  bool isLastMessageRight(int index) {
    if ((index > 0 && getDataContainer().getDataFromMap('CHAT_MESSAGES') != null && 
         getDataContainer().getDataFromMap('CHAT_MESSAGES')[index - 1].peerId != userId) || index == 0) {
      return true;
    } 
    else {
      return false;
    }
  }

  Widget buildItem(int index, ChatMessage message) {
    if (message.peerId == userId) {
      // Right (my message)
      return buildMyMessage(message, index);
    } 
    else {
      // Left (peer message)
      return buildMessage(index, message);
    }
  }

  Container buildMessage(int index, ChatMessage message) {
    return Container(
      child: Column(
        children: <Widget>[
          Row(
            children: <Widget>[
              isLastMessageLeft(index)
                  ? Material(
                      child: CachedNetworkImage(
                        placeholder: (context, url) => Container(
                          child: CircularProgressIndicator(
                            strokeWidth: 1.0,
                            valueColor: AlwaysStoppedAnimation<Color>(themeColor),
                          ),
                          width: 35.0,
                          height: 35.0,
                          padding: EdgeInsets.all(10.0),
                        ),
                        imageUrl: message.peerAvatar.isNotEmpty ? message.peerAvatar : 'assets/images/img_not_available.jpeg',
                        width: 35.0,
                        height: 35.0,
                        fit: BoxFit.cover,
                      ),
                      borderRadius: BorderRadius.all(
                        Radius.circular(18.0),
                      ),
                      clipBehavior: Clip.hardEdge,
                    )
                  : Container(width: 35.0),
                  message.type == ChatMessageType.peer && message.peerAvatar.isEmpty
                  ? Container(
                      child: 
                        Column(children: [
                          Text("From: "+message.peerId, style: TextStyle(color: Colors.cyan, fontWeight: FontWeight.bold)),
                          Text(message.text, style: TextStyle(color: Colors.white)),
                        ]),
                      padding: EdgeInsets.fromLTRB(15.0, 10.0, 15.0, 10.0),
                      width: 200.0,
                      decoration: BoxDecoration(color: primaryColor, borderRadius: BorderRadius.circular(8.0)),
                      margin: EdgeInsets.only(left: 10.0),
                    )
                //: message.type == ChatMessageType.peer && message.peerAvatar.isNotEmpty
                : message.peerAvatar.isNotEmpty ?
                    Container(
                      child: Material(
                        child: CachedNetworkImage(
                          placeholder: (context, url) => Container(
                            child: CircularProgressIndicator(
                              valueColor: AlwaysStoppedAnimation<Color>(themeColor),
                            ),
                            width: 200.0,
                            height: 200.0,
                            padding: EdgeInsets.all(70.0),
                            decoration: BoxDecoration(
                              color: greyColor2,
                              borderRadius: BorderRadius.all(
                                Radius.circular(8.0),
                              ),
                            ),
                          ),
                          errorWidget: (context, url, error) => Material(
                            child: Image.asset(
                              'assets/images/img_not_available.jpeg',
                              width: 200.0,
                              height: 200.0,
                              fit: BoxFit.cover,
                            ),
                            borderRadius: BorderRadius.all(
                              Radius.circular(8.0),
                            ),
                            clipBehavior: Clip.hardEdge,
                          ),
                          imageUrl: message.peerAvatar,
                          width: 200.0,
                          height: 200.0,
                          fit: BoxFit.cover,
                        ),
                        borderRadius: BorderRadius.all(Radius.circular(8.0)),
                        clipBehavior: Clip.hardEdge,
                      ),
                      margin: EdgeInsets.only(left: 10.0),
                    )
                  // : Container(
                  //     child: new Image.asset(
                  //       'assets/images/${message.peerAvatar}.gif',
                  //       width: 100.0,
                  //       height: 100.0,
                  //       fit: BoxFit.cover,
                  //     ),
                  //     margin: EdgeInsets.only(bottom: isLastMessageRight(index) ? 20.0 : 10.0, right: 10.0),
                  //   ),
    
                    : Container(child: 
                        Column(children: [
                          Text("From: "+message.peerId, style: TextStyle(color: Colors.cyan, fontWeight: FontWeight.bold)),
                          Text(message.text, style: TextStyle(color: Colors.white)),
                        ]),
                      padding: EdgeInsets.fromLTRB(15.0, 10.0, 15.0, 10.0),
                      width: 200.0,
                      decoration: BoxDecoration(color: primaryColor, borderRadius: BorderRadius.circular(8.0)),
                      margin: EdgeInsets.only(left: 10.0),
                    )
    
            ],
          ),
    
          // Time
          isLastMessageLeft(index)
              ? Container(child: Text(
                    DateFormat('dd MMM kk:mm').format(message.timestamp),
                    style: TextStyle(color: greyColor, fontSize: 12.0, fontStyle: FontStyle.italic),
                  ),
                  margin: EdgeInsets.only(left: 50.0, top: 5.0, bottom: 5.0),
                )
              : Container()
        ],
        crossAxisAlignment: CrossAxisAlignment.start,
      ),
      margin: EdgeInsets.only(bottom: 10.0),
    );
  }

  Row buildMyMessage(ChatMessage message, int index) {
    return Row(
      children: <Widget>[
        message.type == ChatMessageType.peer && message.peerAvatar.isEmpty
            // Text
            ? Container(
                child: Column(children: [
                  Text("From: "+message.peerId, style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
                  Text(message.text, style: TextStyle(color: primaryColor)),
                ]),
                padding: EdgeInsets.fromLTRB(15.0, 10.0, 15.0, 10.0),
                width: 200.0,
                decoration: BoxDecoration(color: greyColor2, borderRadius: BorderRadius.circular(8.0)),
                margin: EdgeInsets.only(bottom: isLastMessageRight(index) ? 20.0 : 10.0, right: 10.0),
              )
            : message.type == ChatMessageType.peer && message.peerAvatar.isNotEmpty
                // Image
                ? Container(
                    child: Material(
                      child: CachedNetworkImage(
                        placeholder: (context, url) => Container(
                          child: CircularProgressIndicator(
                            valueColor: AlwaysStoppedAnimation<Color>(themeColor),
                          ),
                          width: 200.0,
                          height: 200.0,
                          padding: EdgeInsets.all(70.0),
                          decoration: BoxDecoration(
                            color: greyColor2,
                            borderRadius: BorderRadius.all(
                              Radius.circular(8.0),
                            ),
                          ),
                        ),
                        errorWidget: (context, url, error) => Material(
                          child: Image.asset(
                            'assets/images/img_not_available.jpeg',
                            width: 200.0,
                            height: 200.0,
                            fit: BoxFit.cover,
                          ),
                          borderRadius: BorderRadius.all(
                            Radius.circular(8.0),
                          ),
                          clipBehavior: Clip.hardEdge,
                        ),
                        imageUrl: message.peerAvatar,
                        width: 200.0,
                        height: 200.0,
                        fit: BoxFit.cover,
                      ),
                      borderRadius: BorderRadius.all(Radius.circular(8.0)),
                      clipBehavior: Clip.hardEdge,
                    ),
                    margin: EdgeInsets.only(bottom: isLastMessageRight(index) ? 20.0 : 10.0, right: 10.0),
                  )
                // Sticker
                : Container(
                    child: new Image.asset(
                      'assets/images/${message.peerAvatar}.gif',
                      width: 100.0,
                      height: 100.0,
                      fit: BoxFit.cover,
                    ),
                    margin: EdgeInsets.only(bottom: isLastMessageRight(index) ? 20.0 : 10.0, right: 10.0),
                  ),
      ],
      mainAxisAlignment: MainAxisAlignment.end,
    );
  }

  Widget buildListMessage(List<dynamic> messages) {
    return Flexible(
      child: 
      //groupChatId == '' ? Center(child: CircularProgressIndicator(valueColor: AlwaysStoppedAnimation<Color>(themeColor))) : 
        ListView.builder(
                    padding: EdgeInsets.all(10.0),
                    itemBuilder: (context, index) => buildItem(index, messages[index]),
                    itemCount: messages.length,
                    reverse: true,
                    controller: listScrollController,
            ),
    );
  }
}