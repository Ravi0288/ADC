from django.shortcuts import render
from django.http import HttpResponse
import json
from django.conf import settings
import os


# Function to start UI to view reports
def report_view_index(request):
    context = {
        'title' : 'Welcome',
        'messages' : ['Click on buttons on right side of navigation bar to see the report']
    }
    return render(request, 'pages/dashboard.html', context=context)


# UI for ERS report
def ERS(request):
    # set path based on production or development environment
    if settings.DEBUG:
        ERS_REPORT_PATH = os.path.join(settings.ERS_LOGS_STAGE, 'metadata_report.txt')
    else:
        ERS_REPORT_PATH = os.path.join(settings.ERS_LOGS_PROD, 'metadata_report.txt')

    # open the report file
    with open(ERS_REPORT_PATH, 'r') as f:
        # read lines of the file
        lines = f.readlines()

    # prepare the date to be rendered on HTML page
    context = {
        'title' : 'ERS',
        'messages' : lines
    }

    # render the HTML page
    return render(request, 'pages/dashboard.html', context=context)


# UI view for FAS report
def FAS(request):
    if settings.DEBUG:
        FAS_REPORT_PATH = os.path.join(settings.FAS_LOGS_STAGE, 'metadata_report.txt')
    else:
        FAS_REPORT_PATH = os.path.join(settings.FAS_LOGS_PROD, 'metadata_report.txt')

    with open(FAS_REPORT_PATH, 'r') as f:
        lines = f.readlines()

    return render(request, 'pages/dashboard.html', context={
        'title' : 'FAS',
        'messages' : lines
    })


# UI for FSA report
def FSA(request):
    if settings.DEBUG:
        FSA_REPORT_PATH = os.path.join(settings.FSA_LOGS_STAGE, 'metadata_report.txt')
    else:
        FSA_REPORT_PATH = os.path.join(settings.FSA_LOGS_PROD, 'metadata_report.txt')

    with open(FSA_REPORT_PATH, 'r') as f:
        lines = f.readlines()

    return render(request, 'pages/dashboard.html', context={
        'title' : 'FSA',
        'messages' : lines
    })


# UI for NRCS reports
def NRCS(request):
    if settings.DEBUG:
        NRCS_REPORT_PATH = os.path.join(settings.NRCS_LOGS_STAGE, 'metadata_report.txt')
    else:
        NRCS_REPORT_PATH = os.path.join(settings.NRCS_LOGS_PROD, 'metadata_report.txt')

    with open(NRCS_REPORT_PATH, 'r') as f:
        lines = f.readlines()

    return render(request, 'pages/dashboard.html', context={
        'title' : 'NRCS',
        'messages' : lines
    })


# UI for FSLM report
def FSLM(request):
    if settings.DEBUG:
        FSLM_REPORT_PATH = os.path.join(settings.FSLM_LOGS_STAGE, 'metadata_report.txt')
    else:
        FSLM_REPORT_PATH = os.path.join(settings.FSLM_LOGS_PROD, 'metadata_report.txt')

    with open(FSLM_REPORT_PATH, 'r') as f:
        lines = f.readlines()

    return render(request, 'pages/dashboard.html', context={
        'title' : 'FSLM',
        'messages' : lines
    })