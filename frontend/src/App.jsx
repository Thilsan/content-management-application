import { Navigate, Route, Routes } from 'react-router-dom'
import AdminLayout from './components/AdminLayout.jsx'
import PublicLayout from './components/PublicLayout.jsx'
import RequirePrivilege from './components/RequirePrivilege.jsx'
import LoginPage from './pages/admin/LoginPage.jsx'
import MenuPage from './pages/admin/MenuPage.jsx'
import PageFormPage from './pages/admin/PageFormPage.jsx'
import PageListPage from './pages/admin/PageListPage.jsx'
import PrivilegeListPage from './pages/admin/PrivilegeListPage.jsx'
import RoleListPage from './pages/admin/RoleListPage.jsx'
import TrashPage from './pages/admin/TrashPage.jsx'
import UserListPage from './pages/admin/UserListPage.jsx'
import HomePage from './pages/public/HomePage.jsx'
import PageView from './pages/public/PageView.jsx'

export default function App() {
  return (
    <Routes>
      <Route element={<PublicLayout />}>
        <Route path="/" element={<HomePage />} />
        <Route path="/pages/:slug" element={<PageView />} />
      </Route>

      <Route path="/admin/login" element={<LoginPage />} />

      <Route path="/admin" element={<AdminLayout />}>
        <Route index element={<Navigate to="/admin/pages" replace />} />

        <Route
          path="pages"
          element={
            <RequirePrivilege privilege="pages.view">
              <PageListPage />
            </RequirePrivilege>
          }
        />
        <Route
          path="pages/new"
          element={
            <RequirePrivilege privilege="pages.create">
              <PageFormPage />
            </RequirePrivilege>
          }
        />
        <Route
          path="pages/:id/edit"
          element={
            <RequirePrivilege privilege="pages.update">
              <PageFormPage />
            </RequirePrivilege>
          }
        />
        <Route
          path="trash"
          element={
            <RequirePrivilege privilege="pages.restore">
              <TrashPage />
            </RequirePrivilege>
          }
        />
        <Route
          path="menu"
          element={
            <RequirePrivilege privilege="menus.view">
              <MenuPage />
            </RequirePrivilege>
          }
        />
        <Route
          path="users"
          element={
            <RequirePrivilege privilege="users.view">
              <UserListPage />
            </RequirePrivilege>
          }
        />
        <Route
          path="roles"
          element={
            <RequirePrivilege privilege="roles.view">
              <RoleListPage />
            </RequirePrivilege>
          }
        />
        <Route
          path="privileges"
          element={
            <RequirePrivilege privilege="privileges.view">
              <PrivilegeListPage />
            </RequirePrivilege>
          }
        />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
