package de.quist.app.errorreporter;

import android.os.Bundle;
import android.support.v7.app.ActionBarActivity;

public class ReportingActionBarActivity extends ActionBarActivity {

	private ExceptionReporter exceptionReporter;

	protected ExceptionReporter getExceptionReporter() {
		return exceptionReporter;
	}

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		exceptionReporter = ExceptionReporter.register(this);
		super.onCreate(savedInstanceState);
	}
	
}
