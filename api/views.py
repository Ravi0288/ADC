from django.shortcuts import render
from django.http import HttpResponse

# Create your views here.

def home(request):
    return render(request, 'pages/dashboard.html')

def ERS(request):
    return render(request, 'pages/dashboard.html', context={
        'title' : 'ERS',
        'messages' : ['message 1', 'message 2', 'message 3']
    })