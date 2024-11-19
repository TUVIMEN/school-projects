var site_paths = [];
var site_threadid = 0;
var site_threadpage = 0;
var site_path = "";
var site_pathid = 0;
var site_viewpage = 0;

function change_container(container) {
    var containers = document.getElementsByClassName("container");
    for (i of containers) {
        i.style = "display: none;";
    }
    document.getElementById("main_search").style.display = (container == "container_reglog") ? "none" : "block";
    if (container !== "container_view")
        document.getElementById("search_text").value = "";
    document.getElementById(container).style = "display: block;";
}

function reglog_change(isregister,changeurl) {
    change_container("container_reglog");

    var login_form = document.getElementById("form-login"),register_form = document.getElementById("form-register"), login_button = document.getElementById("button-login"), register_button = document.getElementById("button-register");

    if (isregister == 1) {
        if (changeurl === true)
            window.history.pushState(null,null,"/register");

        login_form.style.display = "none";
        register_form.style.display = "initial";
        register_button.style = "border-color: #50C878; cursor: initial;";
        login_button.style = "border-color: #666; cursor: pointer;";

    } else {
        if (changeurl === true)
            window.history.pushState(null,document.title,"/login");
        register_form.style.display = "none";
        login_form.style.display = "initial";
        login_button.style = "border-color: #50C878; cursor: initial;";
        register_button.style = "border-color: #666; cursor: pointer;";
    }
}

function rlogin(data) {
    if (data.status != 200) {
        data.json().then((d) => {document.getElementById("reglog_error").innerHTML = d.error;});
    } else {
        data.json().then((d) => {setCookie("authkey",d,21);forumtree_change(true);});
    }
}

function checklogin() {
    var email = document.getElementById("email-login").value;
    var password = document.getElementById("password-login").value;
    var errorcont = document.getElementById("reglog_error");
    if (!email || !email.match(/^.+@[0-9a-zA-Z]+\.[a-zA-Z]+$/)) {
        errorcont.innerHTML = !email ? "email field is empty" : "email is incorrect";
        return;
    }
    if (!password) {
        errorcont.innerHTML = "password field is empty";
        return;
    }
    request_reglog("login",null,email,password).then(rlogin);
}

function rregister(data) {
    if (data.status != 201) {
        data.json().then((d) => {document.getElementById("reglog_error").innerHTML = d.error;});
    } else {
        document.getElementById("reglog_error").innerHTML = "successfully registered";
        reglog_change(0,1);
    }
}

function checkregister() {
    var username = document.getElementById("username-register").value;
    var email = document.getElementById("email-register").value;
    var password = document.getElementById("password-register").value;
    var repassword = document.getElementById("repassword-register").value;
    var errorcont = document.getElementById("reglog_error");
    if (!username) {
        errorcont.innerHTML = "username field is empty";
        return;
    }
    if (!email || !email.match(/^.+@[0-9a-zA-Z.]+\.[a-zA-Z]+$/)) {
        errorcont.innerHTML = !email ? "email field is empty" : "email is incorrect";
        return;
    }
    if (!password || !repassword) {
        errorcont.innerHTML = (!password ? "" : "re") + "password field is empty";
        return;
    }
    if (password !== repassword) {
        errorcont.innerHTML = "password and repassword are not the same";
        return;
    }
    request_reglog("register",username,email,password).then(rregister);
}

function checkauthkey() {
    var ret = getCookie("authkey");
    if (ret === "")
        reglog_change(0,true);
    CUBES_AUTHKEY = ret;
    return ret;
}

function forumtree_printpaths() {
    if (site_paths.length) {
        var list = document.getElementById("forumtree_list");
        var out = "";
        var previous = "";
        for (i of site_paths) {
            var x = i["path"].split("/");
            if (previous !== x[0]) {
                if (previous !== undefined)
                    out += "</ul>";
                out += "<h2>" + x[0] + "</h2><ul>";
            }
            out += "<li onclick=\"view_change(" + i["id"] + ",1,true)\">";
            for (j of x.slice(1,-1))
                out += j + "/";
            out += " (" + i["count"] + ")";
            out += "</li>";
            previous = x[0];
        }
        if (site_paths.length)
            out += "</ul>";
        list.innerHTML = out;
    } else
        request_tagpath("paths",CUBES_AUTHKEY).then((d) => {d.json().then((g) => {site_paths = g; forumtree_printpaths();})});
}

function forumtree_change(changeurl) {
    var authkey = checkauthkey();
    if (authkey === "")
        return;
    if (changeurl === true)
        window.history.pushState(null,document.title,"/forums/");
    change_container("container_forumtree");

    forumtree_printpaths();
}

function view_show(pathid,threads) {
    var list = document.getElementById("view_list");
    var pages = document.getElementById("view_pages");
    var last = Number(threads['lastpage']);
    var current = Number(threads['currentpage']);

    var out = "";

    if (current < 6) {
        for (var i = 1; i < current; i++)
            out += "<div class=\"page_tile\" onclick=\"view_change(" + pathid + "," + i + ",true)\">" + i +"</div>";
    } else {
        for (var i = current-5; i < current; i++)
            out += "<div class=\"page_tile\" onclick=\"view_change(" + pathid + "," + i + ",true)\">" + i +"</div>";
    }
    out += "<div class=\"page_tile page_current\">" + i +"</div>";
    for (var i = current+1; i < current+6 && i <= last; i++)
        out += "<div class=\"page_tile\" onclick=\"view_change(" + pathid + "," + i + ",true)\">" + i +"</div>";

    pages.innerHTML = out;
    out = "";

    for (i of threads["threads"]) {
        out += "<div onclick=\"thread_change(" + i["id"] + ",1,true)\"><h2>" + i["title"] + "</h2>" + i["date"] + ", " + i["user"] + ", " + i["posts"] + "</div>"
    }
    
    list.innerHTML = out;
}

function view_addpaths(pathid) {
    if (site_paths.length) {
        site_path = site_paths[pathid]["path"];
        vpath = document.getElementById("view_path");
        vpath.innerHTML = site_path;
        vpath.style.display = "block";
    } else
        request_tagpath("paths",CUBES_AUTHKEY).then((d) => {d.json().then((g) => {site_paths = g; view_addpaths(pathid);})});
}

function view_change(pathid,page,changeurl) {
    var authkey = checkauthkey();
    if (authkey === "")
        return;
    if (changeurl === true) {
        if (page === 0)
            page = 1;
        window.history.pushState(null,document.title,"/forums/"+pathid+"/"+page+"/");
    }
    change_container("container_view");
    site_pathid = pathid;
    site_viewpage = page;

    document.getElementById("view_path").style.display = "block";
    document.getElementById("view_button").style.display = "block";
    view_addpaths(pathid);

    request_view(authkey,pathid,page).then((d) => {d.json().then((g) => {view_show(pathid,g);})});
}

function error_change(title,content) {
    change_container("container_error");
    document.getElementById("error_title").innerHTML = title;
    document.getElementById("error_content").innerHTML = content;
    window.history.pushState(null,document.title,"/error");
}

function npost_change() {
    postform = document.getElementById("thread_post_form");
    postform.style.display = (postform.style.display === "none") ? "block" : "none";
    document.getElementById('thread_post_text').value = "";
}

function npost_send() {
    var authkey = checkauthkey();
    if (authkey === "")
        return;

    postform = document.getElementById("thread_post_form");
    postform.style = "display: none";

    text = document.getElementById('thread_post_text').value;
    if (text.length === 0)
        return;
    request_npost(authkey,site_threadid,text).then((j) => {thread_change(site_threadid,site_threadpage,true);})
}

function thread_reactions_show(id) {
    post = document.getElementById("post"+id);
    reactions = document.getElementById("thread_reactions_container");
    reactions.innerHTML = '<div class="reactions_close" onclick="thread_reactions_close()">X</div>'+post.getElementsByClassName("reactions_list")[0].innerHTML;
    reactions.style = "display: block;";
}

function thread_reactions_close() {
    document.getElementById("thread_reactions_container").style = "display: none;";
}

function nthread_change() {
    threadform = document.getElementById("view_nthread_form");
    threadform.style.display = (threadform.style.display === "none") ? "block" : "none";
    document.getElementById('view_nthread_title').value = "";
}

function nthread_send() {
    var authkey = checkauthkey();
    if (authkey === "")
        return;

    threadform = document.getElementById("view_nthread_form");
    threadform.style = "display: none";

    title = document.getElementById('view_nthread_title').value;
    if (title.length === 0)
        return;
    request_nthread(authkey,title,"main_new",site_path).then((d) => {d.text().then((g) => { thread_change(Number(g),1,true); })});
}


function thread_show(thread) {
    if (Object.hasOwn(thread,'error'))
        return error_change("Error 404: page not found","The requested thread could not be found.");
    document.getElementById("thread_title").innerHTML = thread["title"];
    
    document.getElementById('thread_path').innerHTML = thread["path"];
    
    var list = document.getElementById("thread_list");
    var pages = document.getElementById("thread_pages");
    var last = Number(thread['lastpage']);
    var current = Number(thread['currentpage']);

    var out = "";

    if (current < 6) {
        for (var i = 1; i < current; i++)
            out += "<div class=\"page_tile\" onclick=\"thread_change(" + thread["id"] + "," + i + ",true)\">" + i +"</div>";
    } else {
        for (var i = current-5; i < current; i++)
            out += "<div class=\"page_tile\" onclick=\"thread_change(" + thread["id"] + "," + i + ",true)\">" + i +"</div>";
    }
    out += "<div class=\"page_tile page_current\">" + i +"</div>";
    for (var i = current+1; i < current+6 && i <= last; i++)
        out += "<div class=\"page_tile\" onclick=\"thread_change(" + thread["id"] + "," + i + ",true)\">" + i +"</div>";

    pages.innerHTML = out;
    out = '';
    
    for (i of thread["posts"]) {
        out += "<div id='post" + i["id"] + "' class='post'><div style='display:none;' class='reactions_list'>";
        
        reac = i["reactions"];

        for (j in reac)
            out += "<ul><li>" + reac[j]["date"] + "</li><li>" + reac[j]["user"] + "</li><li>" + reac[j]["reaction"] + "</li></ul>";

        out += "</div><div class='post-date'>" + i["date"] + "</div><div><div class='user-info'><div class='user-avatar'><img alt='" + i['user'] + "' src='" + i['user_avatar'] + "'/></div><h3>" + i["user"] + "</h3>"

        const fields = ["location","joined","lastseen","title","messages","reactionscore","points"]
        for (j in fields)
            if (i["user_" + fields[j]] && i["user_" + fields[j]].length)
                out += "<p>" + fields[j] + ": " + i["user_" + fields[j]] + "</p>";

        out += "</div><div class='post-left'><div class='post-text'>" + i["text"] + "</div><div onclick='thread_reactions_show(" + i["id"] + ")' class='post-reactions'>";
        
        reactions = []

        for (j in i["reactions"]) {
            re = reac[j]["reaction"]
            found = 0;
            for (g in reactions) {
                if (reactions[g].name === re) {
                    reactions[g].num++;
                    found = 1;
                    break;
                }
            }
            if (found === 0)
                reactions.push({"name":re,"num":1});
        }

        for (j in reactions) {
            out += reactions[j].name + ":" + reactions[j].num;
            if (j != reactions.length-1)
                out += ","
        }

        out += "</div></div></div></div>";
    }

    list.innerHTML = out;
}

function thread_change(threadid,page,changeurl) {
    var authkey = checkauthkey();
    if (authkey === "")
        return;
    if (changeurl === true) {
        if (page === 0)
            page = 1;
        window.history.pushState(null,document.title,"/thread/"+threadid+"/"+page+"/");
    }
    site_threadid = threadid;
    site_threadpage = page;
    change_container("container_thread");

    request_thread(authkey,threadid,page).then((d) => {d.json().then((g) => {thread_show(g);})});
}

function search_show(query,threads) {
    console.log(threads)
    var list = document.getElementById("view_list");
    var pages = document.getElementById("view_pages");
    var last = Number(threads['lastpage']);
    var current = Number(threads['currentpage']);

    var out = "";
    pages.innerHTML = out;

    for (i of threads) {
        out += "<div onclick=\"thread_change(" + i["id"] + ",1,true)\"><h2>" + i["title"] + "</h2>" + i["date"] + ", " + i["user"] + ", " + i["posts"] + "</div>"
    }
    
    list.innerHTML = out;
}

function search_change(query,changeurl) {
    var authkey = checkauthkey();
    if (authkey === "")
        return;
    if (changeurl === true)
        window.history.pushState(null,document.title,"/search/"+query+"/");
    change_container("container_view");
    document.getElementById("view_path").style.display = "none";
    document.getElementById("view_button").style.display = "none";
    document.getElementById("view_nthread_form").style.display = "none";

    request_search(authkey,query,"","").then((d) => {d.json().then((g) => {search_show(query,g);})});
}

function ssearch() {
    query = document.getElementById("search_text").value;
    if (query.length === 0)
        return;
    search_change(query,true);
}

function changesite_path(path) {
    var urloptions = path.split("/");
    switch (urloptions[0]) {
        case "":
            if (getCookie("authkey") === "") {
                reglog_change(0,true);
            } else {
                forumtree_change(true);
            }
            break;
        case "login":
            reglog_change(0,false);
            break;
        case "register":
            reglog_change(1,false);
            break;
        case "forums":
            if (urloptions[1] !== "") {
                view_change(urloptions[1],urloptions[2],false);
            } else {
                forumtree_change(false);
            }
            break;
        case "search":
            if (urloptions[1] !== "")
                search_change(urloptions[1],false);
            break;
        case "thread":
            if (urloptions[1] !== "")
                thread_change(urloptions[1],urloptions[2],false);
            break;
        default:
            error_change("Error 404: page not found","Stop it, get some help.");
            break;
    }
}

function changesite(state) {
    changesite_path(window.location.toString().split("/").slice(3).join('/'))
}
