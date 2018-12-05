{{ content() }}
<div class="container">
    <div class="index">
        <div class="title">开发者体验中心</div>
        <div class="group">
            {{ link_to("admin/setting", '参数配置', "class": "btn") }}
            <a href="javascript:void(0);" target="_blank" onclick="popDemo()">弹窗体验</a>
            {{ link_to("api/apiPanel", '呼叫体验', "class": "btn") }}
        </div>
    </div>
</div>

<script>
function popDemo(){
    var extenNo = prompt("请输入分机号码", "1001");

    window.open("{{ web_url }}api/apiPopDemo/?extension="+extenNo);
}
</script>
