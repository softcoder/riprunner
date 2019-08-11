import 'dart:convert';

import '../app_constants.dart';
import '../common/utils.dart';

class AuthResponse {
  final bool status;
  final int expiresIn;
  final String user;
  final String message;
  final String token;

  AuthResponse({this.status, this.expiresIn, this.user, this.message, this.token});

  factory AuthResponse.fromJson(Map<String, dynamic> json) {
    return AuthResponse(
      status: json['status'],
      expiresIn: json['expiresIn'],
      user: json['user'],
      message: json['message'],
      token: json['token'],
    );
  }
}

class Authentication {
  static List<String> sessionIdList = [];

  static Future<AuthResponse> login(String fhid, String user, String pwd, String deviceId) async {
    try {
      //var pwdBytes = utf8.encode(pwd);
      //var pwdBase64Str = base64.encode(pwdBytes);
      String websiteRootUrl = await Utils.getConfigItem<String>(AppConstants.PROPERTY_WEBSITE_URL);

      // String url = websiteRootUrl + (!websiteRootUrl.endsWith('/') ? '/' : '') + 'process_login.php';
      // Map jsonUserMap = {
      //   'fhid': fhid,
      //   'username' : user,
      //   'password' : '',
      //   'p' : pwdBase64Str
      // };
      // return Utils.apiRequest(url, jsonUserMap, APIRequestType.POST,true).
      //   then((data) {
      //     return processLoginResult(data);
      //   }).
      //   catchError((e) {
      //     print(e.toString());
      //     throw e;
      //   });
      String url = websiteRootUrl + (!websiteRootUrl.endsWith('/') ? '/' : '') + 'mobile-login/';
      String params = "rid=$deviceId&fhid=$fhid&uid=$user&upwd=$pwd";
      
      return Utils.apiRequest(url + params, null, APIRequestType.GET,true).
        then((data) {
          //return processLoginResult(data);
                    
          final String responseString = data.trim();
          //Log.i(Utils.TAG, Utils.getLineNumber() + ": Rip Runner response for register_device: " + responseString);
          print("Rip Runner response for register_device: " + responseString);

          if (isGcmErrorBadSenderId(responseString)) {
              // if (gcmLoginErrorCount == 0) {
              //     gcmLoginErrorCount++;

              //     String regid = getGcmDeviceRegistrationId(true);
              //     auth.setGCMRegistrationId(regid);
              //     sendRegistrationIdToBackend(auth);
              // } else {
                  // gcmLoginErrorCount = 0;
                  // runOnUiThread(new Runnable() {
                      // public void run() {
                          // EditText etUpw = (EditText) findViewById(R.id.etUpw);
                          // etUpw.setText("");

                          // TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                          // txtMsg.setText(R.string.invalid_senderid);

                          // getProgressDialog().hide();
                      // }
                  // });
                  return AuthResponse(
                    status: false,
                    expiresIn: 0,
                    user: '',
                    message: 'FCM error, bad sender',
                    token: '',
                  );

              // }
          } 
          else if (isGcmErrorNotRegistered(responseString)) {
              // if (gcmLoginErrorCount == 0) {
              //     gcmLoginErrorCount++;

              //     String regid = getGcmDeviceRegistrationId(true);
              //     auth.setGCMRegistrationId(regid);
              //     sendRegistrationIdToBackend(auth);
              // } else {
              //     gcmLoginErrorCount = 0;
              //     runOnUiThread(new Runnable() {
              //         public void run() {
              //             EditText etUpw = (EditText) findViewById(R.id.etUpw);
              //             etUpw.setText("");

              //             TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
              //             txtMsg.setText(getString(R.string.gcm_device_error, responseString));

              //             getProgressDialog().hide();
              //         }
              //     });
              // }
              return AuthResponse(
                    status: false,
                    expiresIn: 0,
                    user: '',
                    message: 'FCM error, not registered',
                    token: '',
                  );
          } 
          else {
              if (responseString.startsWith("OK=")) {
                  List<String> responseParts = responseString.split("\\|");
                  if (responseParts.length > 2) {
                      String firehallCoords = responseParts[2];
                      List<String> firehallCoordsParts = firehallCoords.split(",");
                      if (firehallCoordsParts.length == 2) {
                          //auth.setFireHallGeoLatitude(firehallCoordsParts[0]);
                          //auth.setFireHallGeoLongitude(firehallCoordsParts[1]);
                      }
                  }

                  //handleRegistrationSuccess(auth);
                  return AuthResponse(
                    status: true,
                    expiresIn: 0,
                    user: user,
                    message: 'Login success',
                    token: '',
                  );                  
              } else {
                  // runOnUiThread(new Runnable() {
                  //     public void run() {
                  //         EditText etUpw = (EditText) findViewById(R.id.etUpw);
                  //         etUpw.setText("");

                  //         TextView txtMsg = (TextView) findViewById(R.id.txtMsg);
                  //         txtMsg.setText(getString(R.string.invalid_login_attempt, responseString));

                  //         getProgressDialog().hide();
                  //     }
                  // });
                  return AuthResponse(
                    status: false,
                    expiresIn: 0,
                    user: '',
                    message: 'Invalid login attempt',
                    token: '',
                  );                  
              }
          }



        }).
        catchError((e) {
          print(e.toString());
          throw e;
        });

    }
    catch(e) {
      print(e.toString());
      throw e;
    }
  }

  static bool isGcmErrorNotRegistered(String responseString) {
      //|GCM_ERROR:
      return (responseString != null && responseString.contains("|FCM_ERROR:"));
  }

  //GCM_ERROR:MismatchSenderId
  static bool isGcmErrorBadSenderId(String responseString) {
      //|GCM_ERROR:
      return (responseString != null && responseString.contains("|FCM_ERROR:MismatchSenderId"));
  }

  static Future<AuthResponse> processLoginResult(String result) async {
    if(result == null || result == '') {
      return null;
    }
    AuthResponse auth = AuthResponse.fromJson(json.decode(result));
    return auth;
  }

  static List<String> getSessionCookies() {
    return sessionIdList;
  }
  static void setSessionCookies(List<String> cookies) {
    sessionIdList = cookies;
  }
  static bool isLoggedIn() {
    return sessionIdList.isNotEmpty;
  }
  static void logout() {
    sessionIdList = [];
  }
}