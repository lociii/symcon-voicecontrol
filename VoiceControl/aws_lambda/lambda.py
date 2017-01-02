import json
import urllib2
import httplib
from uuid import uuid4
import logging

logger = logging.getLogger('lambda')

def build_error_response(reason):
    return {
        'header': {
            'namespace': 'Alexa.ConnectedHome.Control',
            'name': reason,
            'payloadVersion': '2',
            'messageId': str(uuid4())
        },
        'payload': {}
    }

def lambda_handler(event, context):
    request = urllib2.Request('https://oauth.ipmagic.de/forward')
    request.add_header('Content-Type', 'application/json')
    request.add_header('Authorization', event['payload']['accessToken'])

    try:
        response = urllib2.urlopen(request, json.dumps(event))
    except (urllib2.HTTPError, urllib2.URLError, httplib.HTTPException), e:
        logger.exception(e)
        return build_error_response('TargetConnectivityUnstableError')
    except Exception, e:
        logger.exception(e)
        return build_error_response('DriverInternalError')

    if response.getcode() != 200:
        logger.error('invalid response %s' % response.getcode())
        return build_error_response('DriverInternalError')

    try:
        response = json.loads(response.read())
    except ValueError:
        logger.error('failed to decode response')
        return build_error_response('DriverInternalError')

    return response
