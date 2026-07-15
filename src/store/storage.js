(function attachRentalStore(global) {
  const STORAGE_KEY = "rentalin_custom_items";

  function getBaseData() {
    return global.RENTAL_DATA || { rentalItems: [], categories: [] };
  }

  function getCustomItems() {
    try {
      const raw = global.localStorage.getItem(STORAGE_KEY);
      if (!raw) {
        return [];
      }

      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  }

  function saveCustomItems(items) {
    global.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
  }

  function addCustomItem(item) {
    const customItems = getCustomItems();
    const nextItem = {
      ...item,
      id: `custom-${Date.now()}`,
      createdAt: new Date().toISOString(),
    };

    customItems.unshift(nextItem);
    saveCustomItems(customItems);
    return nextItem;
  }

  function getAllItems() {
    const { rentalItems } = getBaseData();
    return [...getCustomItems(), ...rentalItems];
  }

  function getCategoriesWithCounts() {
    const { categories } = getBaseData();
    const items = getAllItems();

    const counts = items.reduce((accumulator, item) => {
      accumulator[item.category] = (accumulator[item.category] || 0) + 1;
      return accumulator;
    }, {});

    const known = new Set(categories.map((category) => category.name));

    const merged = categories.map((category) => ({
      ...category,
      count: counts[category.name] || 0,
    }));

    Object.keys(counts).forEach((name) => {
      if (!known.has(name)) {
        merged.push({ name, icon: "Package", count: counts[name] });
      }
    });

    return merged;
  }

  global.RENTAL_STORE = {
    getAllItems,
    getCustomItems,
    addCustomItem,
    getCategoriesWithCounts,
  };
})(window);
