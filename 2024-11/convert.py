#!/usr/bin/env python3

import os
import json
import mysql.connector
import re

con = mysql.connector.connect(
    user="cubes",
    database="moto",
    password="Calcium",
    host="localhost",
    collation="utf8mb4_unicode_ci",
)
cur = con.cursor()
inserted_count = 0
inserted_limit = 10000

truncate = False


class table:
    @staticmethod
    def _get_index(name):
        cur.execute("select count(*) from " + name + ";")
        return cur.fetchall()[0][0] + 1

    def __init__(self, name):
        self.name = name
        args = []
        cur.execute("describe " + name + ";")
        r = cur.fetchall()
        if len(r) == 0:
            raise Exception("empty description of " + name + " table")

        for i in r:
            isstr = 0
            if (
                i[1][:7] == "varchar"
                or i[1][:4] == "char"
                or i[1][:8] == "datetime"
                or i[1][:4] == "date"
                or i[1][:4] == "text"
            ):
                isstr = 1

            args.append((i[0], isstr))

        self.args = args
        self.index = self._get_index(name)

    def _insert_args(self, args):
        out = ""
        if len(args) != len(self.args) - 1:
            raise Exception(
                "improper number of arguments expected "
                + len(self.args)
                - 1
                + " but got "
                + len(args)
            )

        for i in range(0, len(args)):
            if args[i] is None:
                a = "null"
            else:
                a = str(args[i])
                isstr1 = isinstance(args[i], str)
                isstr2 = self.args[i + 1][1]
                if isstr1 != isstr2:
                    raise Exception("unexpected variable type")
                if isstr1:
                    a = '"' + args[i].replace("\\", "\\\\").replace('"', '\\"') + '"'

            out += "," + a
        return out

    def insert(self, *args, index=None):
        first = index
        if first is None:
            first = "null"

        p = (
            "insert into "
            + self.name
            + " values ("
            + first
            + self._insert_args(args)
            + ");"
        )
        # print(p)
        cur.execute(p)
        global inserted_count
        if inserted_count > inserted_limit:
            inserted_count = 0
            con.commit()

        inserted_count += 1
        self.index += 1

    def _acreate_query_cond(self, args):
        out = ""

        if len(args) != len(self.args) - 1:
            raise Exception(
                "improper number of arguments expected "
                + len(self.args)
                - 1
                + " but got "
                + len(args)
            )

        for i in range(0, len(args)):
            if args[i] is None:
                a = "null"
            else:
                a = str(args[i])
                isstr1 = isinstance(args[i], str)
                isstr2 = self.args[i + 1][1]
                if isstr1 != isstr2:
                    raise Exception("unexpected variable type")
                if isstr1:
                    a = '"' + args[i].replace("\\", "\\\\").replace('"', '\\"') + '"'

            out += " `" + self.args[i + 1][0] + "`=" + a + " and"

        return out[:-4]

    def acreate(self, *args):
        cur.execute(
            "select id from "
            + self.name
            + " where"
            + self._acreate_query_cond(args)
            + ";"
        )
        r = cur.fetchall()
        if len(r) != 0:
            return r[0][0]

        previndex = self.index
        self.insert(*args)
        return previndex


def create_tables():
    out = {}

    cur.execute("show tables;")
    for i in cur.fetchall():
        n = i[0]
        if truncate:
            if n != "currencies":
                cur.execute("truncate " + n + ";")
        else:
            out[n] = table(n)

    if truncate:
        exit()
    return out


tables = create_tables()


def process_location(loc):
    lc_index = tables["locations"].index

    tables["locations"].acreate(
        loc["address"],
        loc["city"],
        loc["region"],
        loc["country"],
        loc["postalCode"],
        loc["shortAddress"],
        loc["canonicals"]["city"],
        loc["canonicals"]["region"],
        loc["canonicals"]["subregion"],
        float(loc["map"]["latitude"]),
        float(loc["map"]["longitude"]),
        int(loc["map"]["zoom"]),
        int(loc["map"]["radius"]),
    )

    return lc_index


def process_workinghour(wh):
    ret = [0, 0]
    if wh is not None:
        r = re.split("[:., ]+", wh)
        ret[0] = "".join(filter(str.isdigit, r[0]))
        if len(r) > 1:
            ret[1] = "".join(filter(str.isdigit, r[1]))
    return ret


def process_seller(data):
    sl = data["advert"]["seller"]
    sl_index = tables["sellers"].index

    phonenumber = ""
    if data.get("phoneNumbers"):
        phonenumber = data["phoneNumbers"][0]

    isprivate = 0
    if sl["type"].lower() == "private":
        isprivate = 1

    name = sl["name"]
    website = sl.get("website")
    if website is None:
        website = ""

    locationid = process_location(sl["location"])

    sl_newindex = tables["sellers"].acreate(
        locationid, website, name, phonenumber, isprivate, None, None
    )
    if sl_newindex != sl_index:
        return sl_newindex

    for i in sl["featuresBadges"]:
        ba_nameid = tables["badge_names"].acreate(i["code"], i["label"])
        tables["badges"].acreate(ba_nameid, sl_index)

    for i in sl["logos"]:
        tables["logos"].acreate(
            i["image"]["src"], i["image"]["alt"], i["type"], sl_index
        )

    if sl.get("workingHours") is not None:
        for i in sl["workingHours"]:
            openat = process_workinghour(i.get("openAt"))
            closeat = process_workinghour(i.get("closeAt"))

            tables["workinghours"].acreate(
                sl_index,
                int(i["day"]),
                int(openat[0]),
                int(openat[1]),
                int(closeat[0]),
                int(closeat[1]),
            )

    if sl.get("services") is not None:
        for i in sl["services"]:
            se_nameid = tables["service_names"].acreate(i["label"], i["iconUrl"])
            tables["services"].acreate(se_nameid, sl_index)

    return sl_index


def process_offer(data):
    data = data["props"]["pageProps"]
    if data.get("advert") is None:
        return
    advert = data["advert"]

    offers = tables["offers"]
    of_index = offers.index

    for i in advert["images"]["photos"]:
        tables["offer_photos"].insert(i["id"], of_index)

    price = float(advert["price"]["value"])
    cur.execute(
        'select id from currencies where name="' + advert["price"]["currency"] + '";'
    )
    currencyid = cur.fetchall()[0][0]

    isactive = 0
    if advert["status"].lower() == "active":
        isactive = 1

    title = advert["title"]
    description = advert["description"]
    created = re.sub("Z.*", " ", advert["createdAt"].replace("T", " "))

    sellerid = process_seller(data)

    categoryid = tables["categories"].acreate(
        advert["category"]["name"], advert["category"]["code"]
    )

    for i in advert["equipment"]:
        eq_categoryid = tables["equipment_categories"].acreate(i["key"], i["label"])
        for j in i["values"]:
            eq_nameid = tables["equipment_names"].acreate(
                j["key"], j["label"], eq_categoryid
            )
            tables["equipments"].insert(eq_nameid, of_index)

    for i in advert["details"]:
        de_nameid = tables["detail_names"].acreate(i["key"], i["label"])
        tables["details"].insert(de_nameid, of_index, i["value"])

    for i in advert["parametersDict"].values():
        pa_nameid = tables["parameter_names"].acreate(i["label"])
        tables["parameters"].insert(pa_nameid, of_index, i["values"][0]["label"])

    offers.insert(
        title,
        price,
        currencyid,
        sellerid,
        created,
        description,
        categoryid,
        isactive,
    )


for i in ["ciezarowe", "motocykle", "osobowe", "czesci"]:
    for j in os.listdir(i):
        if j.endswith("_"):
            continue

        path = i + "/" + j
        r = None
        print(path)
        with open(path, "r") as f:
            r = f.read()
            try:
                process_offer(json.loads(r))
            except:
                pass
            pass

        os.rename(path, path + "_")

con.commit()
