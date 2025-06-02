document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const taskList = document.getElementById('taskList');
    const newTaskInput = document.getElementById('newTask');
    const addTaskBtn = document.getElementById('addTaskBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    
    // Vérifier l'authentification
    const token = localStorage.getItem('userToken');
    if (!token) {
        window.location.href = 'index.html';
        return;
    }

    // Charger les tâches au démarrage
    loadTasks();

    // Événements
    addTaskBtn.addEventListener('click', addTask);
    newTaskInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') addTask();
    });
    logoutBtn.addEventListener('click', logout);

    async function loadTasks() {
        try {
            const response = await fetch('php/tasks.php', {
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Failed to load tasks');
            }
            
            const tasks = await response.json();
            renderTasks(tasks);
        } catch (error) {
            console.error('Error loading tasks:', error);
            alert(error.message);
        }
    }

    async function addTask() {
        const title = newTaskInput.value.trim();
        if (!title) return;

        try {
            const response = await fetch('php/tasks.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({ title })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Failed to add task');
            }

            newTaskInput.value = '';
            loadTasks();
        } catch (error) {
            console.error('Error adding task:', error);
            alert(error.message);
        }
    }

    function renderTasks(tasks) {
        taskList.innerHTML = '';
        tasks.forEach(task => {
            const li = document.createElement('li');
            li.className = 'task-item';
            li.innerHTML = `
                <span class="task-text">${task.title}</span>
                <button class="delete-btn" data-id="${task.id}">Supprimer</button>
            `;
            li.querySelector('.delete-btn').addEventListener('click', () => deleteTask(task.id));
            taskList.appendChild(li);
        });
    }

    async function deleteTask(taskId) {
        if (!confirm('Supprimer cette tâche ?')) return;
        
        try {
            const response = await fetch('php/tasks.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({ id: taskId })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Failed to delete task');
            }

            loadTasks();
        } catch (error) {
            console.error('Error deleting task:', error);
            alert(error.message);
        }
    }

    function logout() {
        localStorage.removeItem('userToken');
        window.location.href = 'index.html';
    }
});