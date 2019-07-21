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

  static Future<AuthResponse> login(String fhid, String user, String pwd) async {
    try {
      var pwdBytes = utf8.encode(pwd);
      var pwdBase64Str = base64.encode(pwdBytes);
      String websiteRootUrl = await Utils.getConfigItem<String>(AppConstants.PROPERTY_WEBSITE_URL);
      String url = websiteRootUrl + (!websiteRootUrl.endsWith('/') ? '/' : '') + 'process_login.php';
      Map jsonUserMap = {
        'fhid': fhid,
        'username' : user,
        'password' : '',
        'p' : pwdBase64Str
      };
      return Utils.apiRequest(url, jsonUserMap, APIRequestType.POST,true).
        then((data) {
          return processLoginResult(data);
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
  static void logout() {
    sessionIdList = [];
  }
}