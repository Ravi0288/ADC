from django.urls import path, include
from rest_framework.routers import DefaultRouter
from .source_json import SyncFromSourceView, URL_to_be_accessed_view, download_and_read_source_json, Download_history_view
from .research_document import download_research_documents, research_docs_view
from django.conf import settings
from django.conf.urls.static import static

router = DefaultRouter()
router.register('fetch', SyncFromSourceView)
router.register('url-to-be-accessed', URL_to_be_accessed_view)
router.register('documents', research_docs_view)
router.register('downloaded-history', Download_history_view)

urlpatterns = [
    path('', include(router.urls)),
    path('download-and-read-source-json/', download_and_read_source_json),
    path('download-research-docs/', download_research_documents),
]


