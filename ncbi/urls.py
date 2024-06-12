from django.urls import path, include
from .downloads import download_xml_file

urlpatterns = [
    path('download-xml-file/', download_xml_file),
]
