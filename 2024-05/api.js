const CUBES_DOMAIN = "http://127.0.0.1";
CUBES_AUTHKEY = null;

function setCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    let expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";SameSite=Lax;path=/";
}

function getCookie(cname) {
    let name = cname + "=";
    let ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

async function request_reglog(option,name,email,password) {
    var funct = "login";
    if (option === "register")
        funct = option;

    const response = await fetch(CUBES_DOMAIN + "/api/" + funct,{
        method: "POST",
        mode: "cors",
        cache: "no-cache",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          // 'Content-Type': 'application/x-www-form-urlencoded',
        },
        redirect: "follow",
        referrerPolicy: "no-referrer",
        body: JSON.stringify({
            "name":name,
            "email":email,
            "password":password
        })
    });
    return response;
}

async function request_thread(authkey,id,page) {
    const response = await fetch(CUBES_DOMAIN + "/api/thread/" + authkey + "/" + id + "/" + page,{
        method: "GET",
        mode: "cors",
        cache: "default",
        credentials: "same-origin",
        redirect: "follow",
        referrerPolicy: "no-referrer",
    });
    return response;
}

async function request_tagpath(option,authkey) {
    funct = "paths"
    if (option === "tags")
        funct = option;
    const response = await fetch(CUBES_DOMAIN + "/api/" + funct + "/" + authkey,{
        method: "GET",
        mode: "cors",
        cache: "default",
        credentials: "same-origin",
        redirect: "follow",
        referrerPolicy: "no-referrer",
    });
    return response;
}

async function request_tags(authkey) {
    return request_tagpath("tags",authkey);
}

async function request_paths(authkey) {
    return request_tagpath("paths",authkey);
}

async function request_view(authkey,path_id,page) {
    const response = await fetch(CUBES_DOMAIN + "/api/view/" + authkey + "/" + path_id + "/" + page,{
        method: "GET",
        mode: "cors",
        cache: "default",
        credentials: "same-origin",
        redirect: "follow",
        referrerPolicy: "no-referrer",
    });
    return response;
}

async function request_vtag(authkey,tag_id,page) {
    const response = await fetch(CUBES_DOMAIN + "/api/vtag/" + authkey + "/" + tag_id + "/" + page,{
        method: "GET",
        mode: "cors",
        cache: "default",
        credentials: "same-origin",
        redirect: "follow",
        referrerPolicy: "no-referrer",
    });
    return response;
}

async function request_search(authkey,search,tag,path) {
    const response = await fetch(CUBES_DOMAIN + "/api/search",{
        method: "POST",
        mode: "cors",
        cache: "no-cache",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          // 'Content-Type': 'application/x-www-form-urlencoded',
        },
        redirect: "follow",
        referrerPolicy: "no-referrer",
        body: JSON.stringify({
            "auth_key":authkey,
            "search": ((search === "") ? null : search),
            "tag":((tag === "") ? null : tag),
            "path":((path === "") ? null : path)
        })
    });
    return response;
}

async function request_nthread(authkey,title,tags,path) {
    const response = await fetch(CUBES_DOMAIN + "/api/nthread",{
        method: "POST",
        mode: "cors",
        cache: "no-cache",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          // 'Content-Type': 'application/x-www-form-urlencoded',
        },
        redirect: "follow",
        referrerPolicy: "no-referrer",
        body: JSON.stringify({
            "auth_key":authkey,
            "title":title,
            "tags":tags,
            "path":path
        })
    });
    return response;
}

async function request_npost(authkey,thread_id,text) {
    const response = await fetch(CUBES_DOMAIN + "/api/npost",{
        method: "POST",
        mode: "cors",
        cache: "no-cache",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          // 'Content-Type': 'application/x-www-form-urlencoded',
        },
        redirect: "follow",
        referrerPolicy: "no-referrer",
        body: JSON.stringify({
            "auth_key":authkey,
            "text":text,
            "thread_id":thread_id,
        })
    });
    return response;
}

async function request_nreaction(authkey,post_id,reaction) {
    const response = await fetch(CUBES_DOMAIN + "/api/nreaction",{
        method: "POST",
        mode: "cors",
        cache: "no-cache",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          // 'Content-Type': 'application/x-www-form-urlencoded',
        },
        redirect: "follow",
        referrerPolicy: "no-referrer",
        body: JSON.stringify({
            "auth_key":authkey,
            "reaction":reaction,
            "post_id":post_id,
        })
    });
    return response;
}

async function request_member(authkey) {
    const response = await fetch(CUBES_DOMAIN + "/api/member/" + authkey,{
        method: "GET",
        mode: "cors",
        cache: "default",
        credentials: "same-origin",
        redirect: "follow",
        referrerPolicy: "no-referrer",
    });
    return response;
}

//request_reglog("register","uookt","uoioot@root.com","root").then((data) => {console.log(data)});
//request_thread(authkey,2,1).then((data) => {console.log(data)});
//request_tagpath("p",authkey).then((data) => {console.log(data)});
//request_view(authkey,2,1).then((data) => {console.log(data)});
//request_vtag(authkey,2,1).then((data) => {console.log(data)});
//request_search(authkey,"dog","","").then((data) => {console.log(data)});
//request_nreaction(authkey,490817,"dog").then((data) => {console.log(data)});
//request_member(authkey).then((data) => {console.log(data)});
