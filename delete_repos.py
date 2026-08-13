#!/usr/bin/env python3
"""
Script para eliminar repositorios de GitHub de forma automática.
Mantiene el repositorio especificado en REPOS_TO_KEEP.

Uso:
    python3 delete_repos.py <tu_token_github>

Para obtener un token:
    1. Ve a https://github.com/settings/tokens
    2. Crea un nuevo token personal (Personal Access Token)
    3. Asigna permisos: "delete_repo" bajo "repo"
    4. Copia el token y úsalo como argumento
"""

import requests
import sys
from typing import List, Dict

# Repositorios que NO se deben eliminar
REPOS_TO_KEEP = {"Castings"}

def get_user_repos(username: str, token: str) -> List[Dict]:
    """Obtiene todos los repositorios del usuario."""
    repos = []
    page = 1
    
    while True:
        url = f"https://api.github.com/user/repos?per_page=100&page={page}"
        headers = {"Authorization": f"token {token}"}
        
        response = requests.get(url, headers=headers)
        
        if response.status_code != 200:
            print(f"❌ Error al obtener repositorios: {response.status_code}")
            print(f"   {response.json().get('message', 'Error desconocido')}")
            sys.exit(1)
        
        data = response.json()
        if not data:
            break
        
        repos.extend(data)
        page += 1
    
    return repos

def delete_repo(owner: str, repo: str, token: str) -> bool:
    """Elimina un repositorio."""
    url = f"https://api.github.com/repos/{owner}/{repo}"
    headers = {"Authorization": f"token {token}"}
    
    response = requests.delete(url, headers=headers)
    
    return response.status_code == 204

def main():
    if len(sys.argv) < 2:
        print("❌ Debes proporcionar tu token de GitHub")
        print(f"Uso: python3 {sys.argv[0]} <tu_token_github>")
        print("\nPara obtener un token:")
        print("  1. Ve a https://github.com/settings/tokens")
        print("  2. Crea un nuevo Personal Access Token")
        print("  3. Asigna permisos: 'delete_repo' bajo 'repo'")
        print("  4. Copia el token y úsalo como argumento")
        sys.exit(1)
    
    token = sys.argv[1]
    
    print("📦 Obteniendo lista de repositorios...")
    repos = get_user_repos("PabloAlvarezSanchez", token)
    
    if not repos:
        print("No se encontraron repositorios.")
        sys.exit(0)
    
    print(f"\n📊 Total de repositorios encontrados: {len(repos)}")
    print(f"🔒 Repositorios a mantener: {', '.join(REPOS_TO_KEEP)}\n")
    
    repos_to_delete = [r for r in repos if r["name"] not in REPOS_TO_KEEP]
    
    if not repos_to_delete:
        print("✅ No hay repositorios que eliminar (solo tienes los que quieres mantener).")
        sys.exit(0)
    
    print(f"🗑️  Repositorios a eliminar: {len(repos_to_delete)}\n")
    
    for repo in repos_to_delete:
        print(f"   - {repo['name']}")
    
    # Confirmación
    print("\n⚠️  ADVERTENCIA: Esta acción es irreversible.")
    confirmation = input("¿Deseas continuar? Escribe 'SI' para confirmar: ").strip().upper()
    
    if confirmation != "SI":
        print("❌ Operación cancelada.")
        sys.exit(0)
    
    # Eliminar repositorios
    print("\n🔄 Eliminando repositorios...\n")
    
    deleted_count = 0
    failed_count = 0
    
    for repo in repos_to_delete:
        repo_name = repo["name"]
        owner = repo["owner"]["login"]
        
        if delete_repo(owner, repo_name, token):
            print(f"✅ {repo_name} - Eliminado")
            deleted_count += 1
        else:
            print(f"❌ {repo_name} - Error al eliminar")
            failed_count += 1
    
    # Resumen
    print(f"\n📈 Resumen:")
    print(f"   ✅ Eliminados: {deleted_count}")
    print(f"   ❌ Errores: {failed_count}")
    print(f"   🔒 Mantenidos: {', '.join(REPOS_TO_KEEP)}")

if __name__ == "__main__":
    main()
