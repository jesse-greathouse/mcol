import { Tabs } from 'flowbite'

export function useFlowbiteTabs({ tabContainerRef, tabElements, defaultTabId, onShow }) {
  return new Tabs(tabContainerRef, tabElements, {
    defaultTabId,
    activeClasses: 'text-blue-600 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-400 border-blue-600 dark:border-blue-500',
    inactiveClasses: 'text-gray-500 hover:text-gray-600 dark:text-gray-400 border-gray-100 hover:border-gray-300 dark:border-gray-700 dark:hover:text-gray-300',
    onShow,
  }, {
    id: tabContainerRef?.id || 'default-tabs',
    override: true,
  })
}
