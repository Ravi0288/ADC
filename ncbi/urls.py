from django.urls import path, include
from .downloads import download_xml_file
from .download import download_xml_file

urlpatterns = [
    path('download-xml-file/', download_xml_file),
    path('download-xml/', download_xml_file),
]
