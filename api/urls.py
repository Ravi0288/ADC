from django.urls import path, include
from rest_framework.routers import DefaultRouter
# from .source_json_for_db import SyncFromSourceView, URL_to_be_accessed_view, download_and_read_source_json, Download_history_view
from .research_document import download_research_documents, research_docs_view
from django.conf import settings
from django.conf.urls.static import static
from .datagov.data_gov_cache import read_json_and_write_in_file
from .datagov.data_gov_migrate import push_to_figshare
from .views import home, ERS
# from django.contrib.auth import views as auth_views
# urlpatterns = [
# path('login/', auth_views.LoginView.as_view(), name='login'),
# ]
router = DefaultRouter()
# router.register('source-json', SyncFromSourceView)
# router.register('url-to-be-accessed', URL_to_be_accessed_view)
# router.register('downloaded-documents', research_docs_view)
# router.register('downloaded-history', Download_history_view)

urlpatterns = [
    path('drf_views', include(router.urls)),
    # path('download-and-read-source-json/', download_and_read_source_json),
    path('download-and-read-source-json/datagov/', read_json_and_write_in_file),
    path('push-to-figshare/datagov/', push_to_figshare),
    # path('download-research-docs/', download_research_documents),

    # ui endpoints
    path('home', home, name='home'),
    path('ers', ERS, name='ers')

]


