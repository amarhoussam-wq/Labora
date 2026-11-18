package dz.labora.app;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Toast;

public class MainActivity extends Activity implements DialogInterface.OnClickListener, View.OnLongClickListener {

    private WebView webView;
    private ProgressBar progressBar;
    private ValueCallback<Uri[]> fileUploadCallback;
    private static final int FILE_CHOOSER_REQUEST_CODE = 1001;
    private static final String PREFS_NAME = "LaboraPrefs";
    private static final String KEY_SERVER_URL = "server_url";
    private static final String DEFAULT_URL = "https://nwc0s04888ws88gscowcksoc.194.34.232.105.sslip.io/patient/book.php";
    private EditText serverUrlInput;

    private static class LaboraWebViewClient extends WebViewClient {
        private final ProgressBar bar;
        private final MainActivity activity;

        public LaboraWebViewClient(MainActivity act, ProgressBar pb) {
            this.activity = act;
            this.bar = pb;
        }

        @Override
        public void onPageStarted(WebView view, String url, Bitmap favicon) {
            super.onPageStarted(view, url, favicon);
            if (bar != null) bar.setVisibility(View.VISIBLE);
        }

        @Override
        public void onPageFinished(WebView view, String url) {
            super.onPageFinished(view, url);
            if (bar != null) bar.setVisibility(View.GONE);
        }

        @Override
        public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
            super.onReceivedError(view, request, error);
            if (bar != null) bar.setVisibility(View.GONE);
            activity.showServerConfigDialog("تعذر الاتصال بالخادم. يرجى التأكد من تشغيل Apache وعنوان السيرفر.");
        }
    }

    private static class LaboraWebChromeClient extends WebChromeClient {
        private final MainActivity activity;
        private final ProgressBar bar;

        public LaboraWebChromeClient(MainActivity act, ProgressBar pb) {
            this.activity = act;
            this.bar = pb;
        }

        @Override
        public void onProgressChanged(WebView view, int newProgress) {
            if (bar != null) {
                bar.setProgress(newProgress);
                if (newProgress == 100) bar.setVisibility(View.GONE);
            }
        }

        @Override
        public boolean onShowFileChooser(WebView webView, ValueCallback<Uri[]> filePathCallback, FileChooserParams fileChooserParams) {
            return activity.handleFileChooser(filePathCallback);
        }
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        webView = findViewById(R.id.webView);
        progressBar = findViewById(R.id.progressBar);

        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setAllowFileAccess(true);
        settings.setAllowContentAccess(true);
        settings.setUseWideViewPort(true);
        settings.setLoadWithOverviewMode(true);
        settings.setSupportZoom(false);
        settings.setBuiltInZoomControls(false);
        settings.setUserAgentString(settings.getUserAgentString() + " LaboraApp");

        webView.setWebViewClient(new LaboraWebViewClient(this, progressBar));
        webView.setWebChromeClient(new LaboraWebChromeClient(this, progressBar));
        webView.setOnLongClickListener(this);

        loadCurrentServerUrl();
    }

    public boolean handleFileChooser(ValueCallback<Uri[]> filePathCallback) {
        if (fileUploadCallback != null) {
            fileUploadCallback.onReceiveValue(null);
        }
        fileUploadCallback = filePathCallback;

        Intent intent = new Intent(Intent.ACTION_GET_CONTENT);
        intent.addCategory(Intent.CATEGORY_OPENABLE);
        intent.setType("*/*");
        intent.putExtra(Intent.EXTRA_MIME_TYPES, new String[]{"image/jpeg", "image/png", "application/pdf"});

        try {
            startActivityForResult(Intent.createChooser(intent, "اختر الوصفة الطبية"), FILE_CHOOSER_REQUEST_CODE);
        } catch (Exception e) {
            fileUploadCallback = null;
            Toast.makeText(this, "لا يمكن فتح منتقي الملفات", Toast.LENGTH_SHORT).show();
            return false;
        }
        return true;
    }

    @Override
    public boolean onLongClick(View v) {
        showServerConfigDialog("تغيير عنوان الخادم المحلي (IP):");
        return true;
    }

    private void loadCurrentServerUrl() {
        SharedPreferences prefs = getSharedPreferences(PREFS_NAME, MODE_PRIVATE);
        String serverUrl = prefs.getString(KEY_SERVER_URL, DEFAULT_URL);
        webView.loadUrl(serverUrl);
    }

    public void showServerConfigDialog(String titleMessage) {
        AlertDialog.Builder builder = new AlertDialog.Builder(this);
        builder.setTitle("إعدادات الاتصال بـ LABORA");
        builder.setMessage(titleMessage);

        SharedPreferences prefs = getSharedPreferences(PREFS_NAME, MODE_PRIVATE);
        String currentUrl = prefs.getString(KEY_SERVER_URL, DEFAULT_URL);

        serverUrlInput = new EditText(this);
        serverUrlInput.setText(currentUrl);
        builder.setView(serverUrlInput);

        builder.setPositiveButton("حفظ واتصال", this);
        builder.setNegativeButton("إلغاء", this);

        builder.show();
    }

    @Override
    public void onClick(DialogInterface dialog, int which) {
        if (which == DialogInterface.BUTTON_POSITIVE && serverUrlInput != null) {
            String newUrl = serverUrlInput.getText().toString().trim();
            if (!newUrl.isEmpty()) {
                SharedPreferences prefs = getSharedPreferences(PREFS_NAME, MODE_PRIVATE);
                prefs.edit().putString(KEY_SERVER_URL, newUrl).commit();
                webView.loadUrl(newUrl);
            }
        } else {
            dialog.cancel();
        }
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode == FILE_CHOOSER_REQUEST_CODE) {
            if (fileUploadCallback != null) {
                Uri[] results = null;
                if (resultCode == Activity.RESULT_OK && data != null) {
                    String dataString = data.getDataString();
                    if (dataString != null) {
                        results = new Uri[]{Uri.parse(dataString)};
                    }
                }
                fileUploadCallback.onReceiveValue(results);
                fileUploadCallback = null;
            }
        }
    }

    @Override
    public void onBackPressed() {
        if (webView != null && webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }
}
