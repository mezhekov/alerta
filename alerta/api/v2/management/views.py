
import time
import datetime

from flask import request, Response, url_for, jsonify, render_template
from alerta.api.v2 import app, db, mq
from alerta.api.v2.switch import Switch, SwitchState

from alerta import get_version
from alerta.common import log as logging

LOG = logging.getLogger(__name__)


switches = [
    Switch('auto-refresh-allow', 'Allow consoles to auto-refresh alerts', SwitchState.ON),
#    Switch('console-api-allow', 'Allow consoles to use the alert API', SwitchState.ON),    # TODO(nsatterl)
#    Switch('sender-api-allow', 'Allow alerts to be submitted via the API', SwitchState.ON),  # TODO(nsatterl)
]


@app.route('/alerta/management')
def management():

    endpoints = [
        url_for('manifest'),
        url_for('properties'),
        url_for('switchboard'),
        url_for('health_check'),
        url_for('status')
    ]
    return render_template('management/index.html', endpoints=endpoints)


@app.route('/alerta/management/manifest')
def manifest():

    manifest = {
        "label": "Alerta",
        "release": get_version(),
        "build": "",
        "date": "",
        "revision": "",
        "description": "The Guardian's Alerta monitoring system",
        "built-by": "rpmbuild",
        "built-on": "el6gen01.gudev.gnl",
    }

    return  jsonify(alerta=manifest)


@app.route('/alerta/management/properties')
def properties():

    properties = ''

    for k, v in app.__dict__.items():
        properties += '%s: %s\n' % (k, v)

    for k, v in app.config.items():
        properties += '%s: %s\n' % (k, v)

    return Response(properties, content_type='text/plain')


@app.route('/alerta/management/switchboard', methods=['GET', 'POST'])
def switchboard():

    if request.method == 'POST':
        for switch in Switch.get_all():
            try:
                value = request.form[switch.name]
                switch.set_state(value)
                LOG.warning('Switch %s set to %s', switch.name, value)
            except KeyError:
                pass

        return render_template('management/switchboard.html', switches=switches)
    else:
        switch = request.args.get('switch', None)
        if switch:
            return render_template('management/switchboard.html',
                                   switches=[Switch.get(switch)])
        else:
            return render_template('management/switchboard.html', switches=switches)


@app.route('/alerta/management/healthcheck')
def health_check():

    try:
        if not mq.is_connected():
            return 'NO_MESSAGE_QUEUE', 503

        if not db.conn.alive():
            return 'NO_DATABASE', 503

        heartbeats = db.get_heartbeats()
        for heartbeat in heartbeats:
            delta = datetime.datetime.utcnow() - heartbeat['receiveTime']
            threshold = float(heartbeat['timeout']) * 4
            if delta.seconds > threshold:
                return 'HEARTBEAT_STALE', 503

    except Exception:
        return 'HEALTH_CHECK_FAILED', 503

    return 'OK'


@app.route('/alerta/management/status')
def status():

    metrics = db.get_metrics()

    auto_refresh_allow = {
        "group": "switch",
        "name": "auto_refresh_allow",
        "type": "text",
        "title": "Alert console auto-refresh",
        "description": "Allows auto-refresh of alert consoles to be turned off remotely",
        "value": "ON" if Switch.get('auto-refresh-allow').is_on() else "OFF",
    }
    metrics.append(auto_refresh_allow)

    return jsonify(application="alerta", time=int(time.time() * 1000), metrics=metrics)
